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
use Bravo3\Bakery\Operation\UpdatePackagesOperation;
use Bravo3\SSH\Credentials\KeyCredential;
use Bravo3\SSH\Credentials\PasswordCredential;
use Hyperion\Dbal\Collection\CriteriaCollection;
use Hyperion\Dbal\Entity\Repository;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\EnvironmentType;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\Exception\CommandFailedException;
use Hyperion\Workflow\Loggers\MemoryLogger;
use Hyperion\Workflow\Mappers\PackagerTypeMapper;
use Hyperion\Workflow\Mappers\RepositoryMapper;

class BakeDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    /**
     * @var string
     */
    private $pkey_file = null;

    /**
     * @var MemoryLogger
     */
    protected $output;

    public function execute()
    {
        $prj     = $this->project;
        $env     = $this->environment;
        $address = $this->getConfig('address');

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
        $schema->addOperation(
            new EnvironmentOperation(
                [
                    'ENV'            => EnvironmentType::BAKERY,
                    'PROJECT_ID'     => $prj->getId(),
                    'ENVIRONMENT_ID' => $env->getId(),
                    'INSTANCE_ID'    => $this->getConfig('instance-id'),
                    'ACTION_ID'      => $this->command->getAction(),
                ]
            )
        );

        // System packages
        if ($prj->getUpdateSystemPackages()) {
            $schema->addOperation(new UpdatePackagesOperation());
        }

        if ($prj->getPackages()) {
            $schema->addOperation(new InstallPackagesOperation($prj->getPackages()));
        }

        // Bake script - first operation after system packages
        if ($prj->getBakeScript()) {
            $schema->addOperation(new ScriptOperation($prj->getBakeScript()));
        }

        // Add all repos
        $criteria = new CriteriaCollection();
        $criteria->add('project', $this->project->getId());
        $repos = $this->dbal->search(Entity::REPOSITORY(), $criteria);
        /** @var Repository $repo */
        foreach ($repos as $repo) {
            $schema->addOperation(new CodeCheckoutOperation(RepositoryMapper::DbalToBakery($repo)));
        }

        // Environment script
        if ($env->getScript()) {
            $schema->addOperation(new ScriptOperation($env->getScript()));
        }

        // Do the bakey bakey
        try {
            $bakery->bake($schema, 180);
        } catch (\Exception $e) {
            throw new CommandFailedException("Bakery failed (".$e->getMessage().")", 0, $e);
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

    /**
     * Create a file on the filesystem containing the private key file
     *
     * This file will be zeroed and removed, but it's (ephemeral) existence is a security concern.
     *
     * @param string $certificate
     * @return string Filename to private key file
     */
    protected function createPrivateKey($certificate)
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'bakery-');
        chmod($temp_file, 0600);
        file_put_contents($temp_file, $certificate);
        $this->pkey_file = $temp_file;
        return $temp_file;
    }

    /**
     * Remove a generated private key file, if it exists
     */
    protected function cleanPrivateKey()
    {
        if ($this->pkey_file) {
            // Zero the file out first so the data can't be recovered
            $zero = str_repeat(chr(0), filesize($this->pkey_file));
            file_put_contents($this->pkey_file, $zero);

            // Delete
            unlink($this->pkey_file);
        }
    }

}
