<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Enum\EnvironmentType;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;

class AbstractCommandDriver
{
    use ConfigTrait;

    /**
     * @var WorkflowCommand
     */
    protected $command;

    /**
     * @var CloudService
     */
    protected $service;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var PoolInterface
     */
    protected $pool;

    function __construct(
        WorkflowCommand $command,
        CloudService $service,
        Project $project,
        Environment $environment,
        PoolInterface $pool
    ) {
        $this->command      = $command;
        $this->service      = $service;
        $this->project      = $project;
        $this->environment  = $environment;
        $this->pool         = $pool;
        $this->config       = $command->getParams();
    }

    /**
     * Check if we're in a production environment
     *
     * @return bool
     */
    protected function isProd()
    {
        return $this->environment->getEnvironmentType() === EnvironmentType::PRODUCTION();
    }

    /**
     * Check if we're in a bakery environment
     *
     * @return bool
     */
    protected function isBakery()
    {
        return $this->environment->getEnvironmentType() === EnvironmentType::BAKERY();
    }

    /**
     * Check if we're in a test/CI environment
     */
    protected function isTest()
    {
        return $this->environment->getEnvironmentType() === EnvironmentType::TEST();
    }

} 