<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ApplicationEnvironment;

/**
 * Abstract command driver
 */
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
     * @var PoolInterface
     */
    protected $pool;

    function __construct(WorkflowCommand $command, CloudService $service, Project $project, PoolInterface $pool)
    {
        $this->command = $command;
        $this->service = $service;
        $this->project = $project;
        $this->pool    = $pool;
        $this->config  = $command->getParams();
    }

    /**
     * Check if we're in a production environment
     *
     * @return bool
     */
    protected function isProd()
    {
        return $this->command->getEnvironment() === ApplicationEnvironment::PRODUCTION;
    }

    /**
     * Check if we're in a bakery environment
     *
     * @return bool
     */
    protected function isBakery()
    {
        return $this->command->getEnvironment() === ApplicationEnvironment::BAKERY;
    }

    /**
     * Check if we're in a test/CI environment
     */
    protected function isTest()
    {
        return $this->command->getEnvironment() === ApplicationEnvironment::TEST;
    }

}
 