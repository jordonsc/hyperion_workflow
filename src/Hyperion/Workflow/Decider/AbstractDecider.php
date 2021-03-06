<?php
namespace Hyperion\Workflow\Decider;

use Bravo3\Cache\PoolInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\Entity\Distribution;
use Hyperion\Dbal\Enum\DistributionStatus;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\StackManager;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;


/**
 * Generic decider functions
 */
class AbstractDecider
{
    use ConfigTrait;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var DataManager
     */
    protected $dbal;

    /**
     * @var StackManager
     */
    protected $sm;

    /**
     * @var WorkflowCommand[]
     */
    protected $commands = [];

    /**
     * @var string
     */
    protected $reason = null;

    function __construct(Action $action, PoolInterface $cache, DataManager $dbal, StackManager $sm)
    {
        $this->action = $action;
        $this->pool   = $cache;
        $this->dbal   = $dbal;
        $this->sm     = $sm;
    }

    /**
     * Get the reason/message of the result
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Get commands
     *
     * @return WorkflowCommand[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get a real-time workflow state
     *
     * This will automatically prefix with the action ID
     *
     * @see #getNsPrexfix()
     * @param string $key
     * @param mixed  $default
     * @return string
     */
    protected function getState($key, $default = null)
    {
        $item = $this->pool->getItem($this->getNsPrefix().$key);
        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Set a real-time workflow state item via the cache
     *
     * This will automatically prefix with the action ID
     *
     * @see #getNsPrexfix()
     * @param string $key
     * @param string $value
     * @param int    $ttl
     */
    protected function setState($key, $value, $ttl = 3600)
    {
        // NB: performance bump here, we really shouldn't need to pull before pushing :(
        $item = $this->pool->getItem($this->getNsPrefix().$key);
        $item->set($value, $ttl);
    }

    /**
     * Update the action phase and/or output
     *
     * If either the phase or output or null, they will not be updated. In rare cases, a command driver may also
     * have updated the progress (phase/output) to report on a long, single-threaded task.
     *
     * @param string $phase
     * @param string $output
     */
    protected function progress($phase = null, $output = null)
    {
        if ($phase) {
            $this->action->setPhase($phase);
        }

        if ($output) {
            $this->action->setOutput($output);
        }

        $this->dbal->update($this->action);
    }

    /**
     * Get the cache namespace prefix
     *
     * @return string
     */
    protected function getNsPrefix()
    {
        return $this->action->getId().'-';
    }


    /**
     * Called by the DecisionManager when the workflow completes
     */
    public function onComplete()
    {
    }

    /**
     * Called by the DecisionManager when the workflow fails
     */
    public function onFail()
    {
    }

    /**
     * Set the distribution status
     *
     * @param DistributionStatus $status
     * @return bool
     */
    protected function setDistributionStatus(DistributionStatus $status)
    {
        if (!$this->action->getDistribution()) {
            return false;
        }

        /** @var Distribution $distro */
        $distro = $this->dbal->retrieve(Entity::DISTRIBUTION(), $this->action->getDistribution());
        if (!$distro) {
            return false;
        }

        $distro->setStatus($status);
        $this->dbal->update($distro);
        return true;
    }

    /**
     * Tear-down the current distribution
     */
    protected function tearDown()
    {
        if ($distro = $this->action->getDistribution()) {
            $this->sm->tearDown($distro);
        }
    }

    /**
     * Tear down all other distributions with the same name + project
     */
    protected function tearDownPrevious()
    {
        if ($distro = $this->action->getDistribution()) {
            $this->sm->tearDownOther($this->action->getDistribution());
        }
    }

}
 