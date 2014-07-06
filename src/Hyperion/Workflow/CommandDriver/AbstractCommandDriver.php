<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Enum\Entity;
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

    /**
     * @var DataManager
     */
    protected $dbal;

    /**
     * @var Action
     */
    protected $action = null;

    function __construct(
        WorkflowCommand $command,
        CloudService $service,
        Project $project,
        Environment $environment,
        PoolInterface $pool,
        DataManager $dbal
    ) {
        $this->command     = $command;
        $this->service     = $service;
        $this->project     = $project;
        $this->environment = $environment;
        $this->pool        = $pool;
        $this->dbal        = $dbal;
        $this->config      = $command->getParams();
    }

    /**
     * Keep a copy of the action, so we can update the action phase/output to show progress
     */
    protected function initAction()
    {
        if (!$this->action) {
            $this->action = $this->dbal->retrieve(Entity::ACTION(), $this->command->getAction());
        }
    }

    /**
     * Update the action phase and/or output
     *
     * If either the phase or output or null, they will not be updated. Be careful with this function, as it updates
     * the entire action, not the workflow task. You would only use this if you command driver would run once during
     * the entire workflow.
     *
     * @param string $phase
     * @param string $output
     */
    protected function progress($phase = null, $output = null)
    {
        $this->initAction();

        if ($phase) {
            $this->action->setPhase($phase);
        }

        if ($output) {
            $this->action->setOutput($output);
        }

        $this->dbal->update($this->action);
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

    /**
     * Set a cache key
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    protected function setState($key, $value, $ttl = 3600)
    {
        $this->pool->getItem($key)->set($value, $ttl);
    }

} 