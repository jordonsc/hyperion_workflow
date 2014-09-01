<?php
namespace Hyperion\Workflow\CommandDriver\Bakery;

use Bravo3\Bakery\Bakery;
use Bravo3\Bakery\Entity\Host;
use Bravo3\Bakery\Entity\Schema;
use Bravo3\Bakery\Enum\Phase;
use Bravo3\Bakery\Operation\CodeCheckoutOperation;
use Bravo3\Bakery\Operation\EnvironmentOperation;
use Bravo3\Bakery\Operation\InstallPackagesOperation;
use Bravo3\Bakery\Operation\ScriptOperation;
use Bravo3\Bakery\Operation\StartServicesOperation;
use Bravo3\Bakery\Operation\UpdatePackagesOperation;
use Bravo3\SSH\Credentials\KeyCredential;
use Bravo3\SSH\Credentials\PasswordCredential;
use Hyperion\Dbal\Collection\CriteriaCollection;
use Hyperion\Dbal\Entity\Distribution;
use Hyperion\Dbal\Entity\Repository;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\EnvironmentType;
use Hyperion\Dbal\Enum\Packager;
use Hyperion\Dbal\Utility\TagStringHelper;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Traits\RemoteTrait;
use Hyperion\Workflow\Exception\CommandFailedException;
use Hyperion\Workflow\Loggers\MemoryLogger;
use Hyperion\Workflow\Mappers\PackagerTypeMapper;
use Hyperion\Workflow\Mappers\RepositoryMapper;

/**
 * Bake or build a machine
 *
 * The differences between a bake, build and deploy will be determined by the environment type
 */
class BakeDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use RemoteTrait;

    /**
     * @var MemoryLogger
     */
    protected $output;

    public function execute()
    {
        $prj     = $this->project;
        $env     = $this->environment;
        $address = $this->getConfig('address');

        /** @var Distribution $distro */
        if ($this->action->getDistribution()) {
            $distro = $this->dbal->retrieve(Entity::DISTRIBUTION(), $this->action->getDistribution());
        } else {
            $distro = null;
        }


        if (!$address) {
            // Check for a pub/priv address and pick using the environment network scope
            $private = $this->getConfig('address-private');
            $public  = $this->getConfig('address-public');
            $address = $this->environment->getPrivateNetwork() ? $private : $public;

            if (!$address) {
                throw new CommandFailedException("Require an `address` or an `address-public` and `address-private` combo");
            }
        }

        // Prep the bakery service
        if ($env->getSshPkey()) {
            $pkey       = $this->createPrivateKey($env->getSshPkey());
            $credential = new KeyCredential($env->getSshUser(), null, $pkey, $env->getSshPassword());
        } else {
            $credential = new PasswordCredential($env->getSshUser(), $env->getSshPassword());
        }

        // Prepare a callback function for the bakery to report progress
        $callback = function (Phase $phase, $step, $total, $message) {
            $this->status($phase, $step, $total, $message);
        };

        // Console output will be stored here, we can use this to update the DBAL Action record
        $this->output = new MemoryLogger();

        // Bakery service
        $bakery = new Bakery(new Host($address, $env->getSshPort(), $credential), $this->output, $callback);

        // Prepare a schema
        $schema = new Schema(PackagerTypeMapper::dbalToBakery($prj->getPackager()));

        // Set environment variables the bake scripts might want to use
        $environments = [
            'PROJECT_ID'     => $prj->getId(),
            'ENV_TYPE'       => $env->getEnvironmentType()->key(),
            'ENVIRONMENT_ID' => $env->getId(),
            'INSTANCE_ID'    => $this->getConfig('instance-id'),
            'ACTION_ID'      => $this->command->getAction(),
        ];

        if ($prj->getPackager() == Packager::APT()) {
            $environments['DEBIAN_FRONTEND'] = 'noninteractive';
        }

        $schema->addOperation(new EnvironmentOperation($environments));

        if ($this->isBakery()) {
            // System packages
            if ($prj->getUpdateSystemPackages()) {
                $schema->addOperation(new UpdatePackagesOperation());
            }

            if (count($prj->getPackages())) {
                $schema->addOperation(new InstallPackagesOperation($prj->getPackages()));
            }
        }

        // Add all repos
        $repos = $this->dbal->getRelatedEntities($prj, Entity::REPOSITORY());

        $helper     = new TagStringHelper();
        $tag_string = $distro->getTagString();
        $tags       = $helper->parseTagString($tag_string);

        /** @var Repository $repo */
        foreach ($repos as $repo) {
            $proxy = null;
            if ($repo->getProxy()) {
                $proxy = $this->dbal->retrieve(Entity::PROXY(), $repo->getProxy());
            }

            $payload = RepositoryMapper::DbalToBakery($repo, $proxy);

            if (isset($tags[$repo->getId()])) {
                $payload->setTag($tags[$repo->getId()]);
            }

            $op = new CodeCheckoutOperation($payload);
            $op->setRunAsRoot(true);
            $schema->addOperation($op);
        }

        if ($this->isBakery()) {
            // Bake script
            if ($prj->getBakeScript()) {
                $op = new ScriptOperation($prj->getBakeScript());
                $op->setRunAsRoot(true);
                $schema->addOperation($op);
            }
        } else {
            // Launch script
            if ($prj->getLaunchScript()) {
                $op = new ScriptOperation($prj->getBakeScript());
                $op->setRunAsRoot(true);
                $schema->addOperation($op);
            }
        }

        // Environment script
        if ($env->getScript()) {
            $op = new ScriptOperation($env->getScript());
            $op->setRunAsRoot(true);
            $schema->addOperation($op);
        }

        if (!$this->isBakery()) {
            // Start services
            $schema->addOperation(new StartServicesOperation($prj->getServices()));
        }

        // Do the bakey bakey
        try {
            $bakery->bake($schema, 180);
        } catch (\Exception $e) {
            throw new CommandFailedException($e->getMessage(), 0, $e);
        } finally {
            $this->cleanPrivateKey();
            $this->progress(null, $this->output->getLog());
        }
    }

    /**
     * Status callback from the bakery
     *
     * @param Phase  $phase
     * @param int    $step
     * @param int    $total
     * @param string $message
     */
    protected function status(Phase $phase, $step, $total, $message)
    {
        // Update the DBAL
        $this->progress($phase->key(), $this->output->getLog());
    }


}
