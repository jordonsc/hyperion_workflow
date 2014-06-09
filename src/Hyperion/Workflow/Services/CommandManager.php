<?php
namespace Hyperion\Workflow\Services;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Entity\Aws\AwsCredential;
use Bravo3\CloudCtrl\Entity\Azure\AzureCredential;
use Bravo3\CloudCtrl\Entity\Google\GoogleCredential;
use Bravo3\CloudCtrl\Enum\Provider as CloudProvider;
use Bravo3\CloudCtrl\Interfaces\Credentials\CredentialInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Bravo3\NetworkProxy\Implementation\HttpProxy;
use Bravo3\NetworkProxy\Implementation\SocksProxy;
use Bravo3\NetworkProxy\NetworkProxyInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Credential;
use Hyperion\Dbal\Entity\HyperionEntity;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Entity\Proxy;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\Provider;
use Hyperion\Dbal\Enum\ProxyType;
use Hyperion\Dbal\Exception\NotFoundException;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\CreateInstanceDriver;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ApplicationEnvironment;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Exception\CommandFailedException;

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
            throw new CommandFailedException("Invalid project");
        }

        /** @var Credential $credentials */
        switch ($command->getEnvironment()) {
            default:
            case ApplicationEnvironment::TEST:
            case ApplicationEnvironment::BAKERY:
                $credentials = $this->getEntity(Entity::CREDENTIAL(), $project->getTestCredential());
                $proxy       = $this->getEntity(Entity::PROXY(), $project->getTestProxy());
                break;
            case ApplicationEnvironment::PRODUCTION:
                $credentials = $this->getEntity(Entity::CREDENTIAL(), $project->getProdCredential());
                $proxy       = $this->getEntity(Entity::PROXY(), $project->getProdProxy());
                break;

        }

        if (!$credentials) {
            throw new CommandFailedException("Invalid credentials");
        }

        // Find the appropriate action function and execute
        $service = $this->buildCloudService($project, $credentials, $proxy);
        $driver  = $this->getDriverForCommand($command, $service, $project);
        $driver->execute();
    }

    /**
     * Build an appropriate command driver
     *
     * @param WorkflowCommand $command
     * @param CloudService    $service
     * @param Project         $project
     * @return CommandDriverInterface
     * @throws CommandFailedException
     */
    protected function getDriverForCommand(WorkflowCommand $command, CloudService $service, Project $project)
    {
        switch ($command->getCommand()) {
            default:
                throw new CommandFailedException("Unknown command");
            case CommandType::LAUNCH_INSTANCE:
                return new CreateInstanceDriver($command, $service, $project, $this->pool);
        }
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
     * @param Project    $project
     * @param Credential $credentials
     * @param Proxy      $proxy
     * @return CloudService
     */
    protected function buildCloudService(Project $project, Credential $credentials, Proxy $proxy = null)
    {
        $cloud_credentials = $this->buildCredentials($credentials);
        $cloud_proxy       = $proxy ? $this->buildProxy($proxy) : null;

        $provider = null;
        switch ($credentials->getProvider()) {
            default:
                throw new CommandFailedException("Unknown provider");
            case Provider::AWS():
                $provider = CloudProvider::AWS;
                break;
            case Provider::GOOGLE_CLOUD():
                $provider = CloudProvider::GOOGLE;
                break;
            case Provider::WINDOWS_AZURE():
                $provider = CloudProvider::AZURE;
                break;
        }

        return CloudService::createCloudService(
            $provider,
            $cloud_credentials,
            $credentials->getRegion(),
            $cloud_proxy
        );
    }

    /**
     *  Create a CredentialInterface from a DBAL entity
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
                throw new CommandFailedException("Unknown provider");
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
                throw new CommandFailedException("Unknown proxy type");
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
 