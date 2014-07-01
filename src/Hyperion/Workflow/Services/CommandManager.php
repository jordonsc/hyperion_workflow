<?php
namespace Hyperion\Workflow\Services;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Entity\Aws\AwsCredential;
use Bravo3\CloudCtrl\Entity\Azure\AzureCredential;
use Bravo3\CloudCtrl\Entity\Google\GoogleCredential;
use Bravo3\CloudCtrl\Interfaces\Credentials\CredentialInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Bravo3\NetworkProxy\Implementation\HttpProxy;
use Bravo3\NetworkProxy\Implementation\SocksProxy;
use Bravo3\NetworkProxy\NetworkProxyInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Credential;
use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Entity\HyperionEntity;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Entity\Proxy;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\Provider;
use Hyperion\Dbal\Enum\ProxyType;
use Hyperion\Dbal\Exception\NotFoundException;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Exception\CommandFailedException;
use Hyperion\Workflow\Mappers\ProviderMapper;

/**
 * Executes commands
 */
class CommandManager
{
    /**
     * @var DataManager
     */
    protected $dm;

    /**
     * @var PoolInterface
     */
    protected $pool;

    function __construct(DataManager $dm, PoolInterface $pool)
    {
        $this->dm   = $dm;
        $this->pool = $pool;
    }

    /**
     * Execute a command
     *
     * @param WorkflowCommand $command
     * @throws CommandFailedException
     */
    public function execute(WorkflowCommand $command)
    {
        // Get project & credentials
        /** @var Project $project */
        $project = $this->getEntity(Entity::PROJECT(), $command->getProject());
        if (!$project) {
            throw new CommandFailedException("Invalid or missing project");
        }

        /** @var Environment $environment */
        $environment = $this->getEntity(Entity::ENVIRONMENT(), $command->getEnvironment());
        if (!$environment) {
            throw new CommandFailedException("Invalid or missing environment");
        }

        /** @var Credential $credentials */
        $credentials = $this->getEntity(Entity::CREDENTIAL(), $environment->getCredential());
        if (!$credentials) {
            throw new CommandFailedException("Invalid or missing credentials");
        }

        /** @var Proxy $proxy */
        $proxy = $this->getEntity(Entity::PROXY(), $environment->getProxy());

        // Find the appropriate action function and execute
        $service = $this->buildCloudService($credentials, $proxy);
        $driver  = $this->getDriverForCommand($command, $service, $project, $environment);
        $driver->execute();
    }

    /**
     * Build an appropriate command driver
     *
     * @param WorkflowCommand $command
     * @param CloudService    $service
     * @param Project         $project
     * @param Environment     $environment
     * @throws CommandFailedException
     * @return CommandDriverInterface
     */
    protected function getDriverForCommand(
        WorkflowCommand $command,
        CloudService $service,
        Project $project,
        Environment $environment
    ) {
        $class = 'Hyperion\Workflow\CommandDriver\\'.$command->getCommand();

        if (!class_exists($class)) {
            throw new CommandFailedException("Command does not exist: ".$command->getCommand());
        }

        return new $class($command, $service, $project, $environment, $this->pool);
    }

    /**
     * Returns a DBAL entity or null if it doesn't exist
     *
     * @param Entity $entity
     * @param int    $id
     * @return HyperionEntity|null
     */
    protected function getEntity(Entity $entity, $id)
    {
        if (!$id) {
            return null;
        }

        try {
            return $this->dm->retrieve($entity, $id);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Create a cloud service out of DBAL entities
     *
     * @param Credential $credentials
     * @param Proxy      $proxy
     * @return CloudService
     */
    protected function buildCloudService(Credential $credentials, Proxy $proxy = null)
    {
        $cloud_credentials = $this->buildCredentials($credentials);
        $cloud_proxy       = $proxy ? $this->buildProxy($proxy) : null;

        $provider = ProviderMapper::DbalToCloudCtrl($credentials->getProvider());
        if (!$provider) {
            throw new CommandFailedException("Unknown provider (".$credentials->getProvider().")");
        }

        return CloudService::createCloudService(
            $provider,
            $cloud_credentials,
            $credentials->getRegion(),
            $cloud_proxy
        );
    }

    /**
     * Create a CredentialInterface from a DBAL entity
     *
     * TODO: Google is broken - need more details in the DBAL entity
     *
     * @param Credential $credentials
     * @return CredentialInterface
     * @throws CommandFailedException
     */
    protected function buildCredentials(Credential $credentials)
    {
        switch ($credentials->getProvider()) {
            default:
                throw new CommandFailedException("Unknown provider (".$credentials->getProvider().")");
            case Provider::AWS():
                $cloud_credentials = new AwsCredential(
                    $credentials->getAccessKey(),
                    $credentials->getSecret(),
                    $credentials->getRegion()
                );
                break;
            case Provider::GOOGLE_CLOUD():
                $cloud_credentials = new GoogleCredential(
                    $credentials->getAccessKey(),
                    '--account name',
                    $credentials->getSecret(), // private key file
                    '--project id',
                    'Hyperion'
                );
                break;
            case Provider::WINDOWS_AZURE():
                $cloud_credentials = new AzureCredential(
                    $credentials->getAccessKey(),
                    $credentials->getSecret(),
                    $credentials->getRegion()
                );
                break;
        }

        return $cloud_credentials;
    }

    /**
     * Create a NetworkProxyInterface from DBAL entity
     *
     * @param Proxy $proxy
     * @return NetworkProxyInterface
     * @throws CommandFailedException
     */
    protected function buildProxy(Proxy $proxy)
    {
        switch ($proxy->getType()) {
            default:
                throw new CommandFailedException("Unknown proxy type (".$proxy->getType().")");
            case ProxyType::SOCKS5():
                return new SocksProxy(
                    $proxy->getHostname(),
                    $proxy->getPort(),
                    $proxy->getUsername() ? : null,
                    $proxy->getPassword() ? : null
                );
            case ProxyType::HTTP():
                return new HttpProxy(
                    $proxy->getHostname(),
                    $proxy->getPort(),
                    $proxy->getUsername() ? : null,
                    $proxy->getPassword() ? : null
                );
        }
    }

}
 