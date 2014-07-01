<?php
namespace Hyperion\Workflow\CommandDriver\Instance;

use Bravo3\Bakery\Bakery;
use Bravo3\Bakery\Entity\Host;
use Bravo3\Bakery\Entity\Schema;
use Bravo3\Bakery\Operation\EnvironmentOperation;
use Bravo3\Bakery\Operation\InstallPackagesOperation;
use Bravo3\Bakery\Operation\ScriptOperation;
use Bravo3\Bakery\Operation\UpdatePackagesOperation;
use Bravo3\SSH\Credentials\KeyCredential;
use Bravo3\SSH\Credentials\PasswordCredential;
use Hyperion\Dbal\Enum\EnvironmentType;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\Loggers\OutputLogger;
use Hyperion\Workflow\Mappers\PackagerTypeMapper;

class BakeDriver extends AbstractCommandDriver implements CommandDriverInterface
{

    private $pkey_file = null;


    public function execute()
    {
        $prj     = $this->project;
        $env     = $this->environment;
        $address = $this->getConfig('address');

        // Prep the bakery service
        if ($env->getSshPkey()) {
            $pkey = $this->createPrivateKey($env->getSshPkey());
            $credential = new KeyCredential($env->getSshUser(), null, $pkey, $env->getSshPassword());
        } else {
            $credential = new PasswordCredential($env->getSshUser(), $env->getSshPassword());
        }

        $output = new OutputLogger('/tmp/bake-'.$this->command->getAction().'-out.log');
        $bakery = new Bakery(new Host($address, $env->getSshPort(), $credential), $output);

        // Prepare a schema
        $schema = new Schema(PackagerTypeMapper::dbalToBakery($prj->getPackager()));

        // Set environment variables the bake scripts might want to use
        $schema->addOperation(
            new EnvironmentOperation(
                [
                    'ENV'            => EnvironmentType::BAKERY,
                    'ENVIRONMENT_ID' => $env->getId(),
                    'INSTANCE_ID'    => $this->getConfig('instance-id'),
                    'PROJECT_ID'     => $prj->getId(),
                    'EVENT_ID'       => $this->command->getAction(),
                ]
            )
        );

        if ($prj->getUpdateSystemPackages()) {
            $schema->addOperation(new UpdatePackagesOperation());
        }

        if ($prj->getPackages()) {
            $schema->addOperation(new InstallPackagesOperation($prj->getPackages()));
        }

        if ($prj->getBakeScript()) {
            $schema->addOperation(new ScriptOperation($prj->getBakeScript()));
        }

        if ($env->getScript()) {
            $schema->addOperation(new ScriptOperation($env->getScript()));
        }

        // Do the bakey bakey
        $bakery->bake($schema);

        $this->cleanPrivateKey();
    }


    /**
     * Create a file on the filesystem containing the private key file
     *
     * This file will be removed, but it's existence is a security concern.
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
            unlink($this->pkey_file);
        }
    }

}
