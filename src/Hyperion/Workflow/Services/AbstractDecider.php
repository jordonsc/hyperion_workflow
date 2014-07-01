<?php
namespace Hyperion\Workflow\Services;

use Bravo3\Cache\PoolInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
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
     * @var WorkflowCommand[]
     */
    protected $commands = [];

    /**
     * @var string
     */
    protected $reason = null;

    function __construct(Action $action, PoolInterface $cache, DataManager $dbal)
    {
        $this->action = $action;
        $this->pool   = $cache;
        $this->dbal   = $dbal;
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
     * Parse workflow data into the config, you can use this to read any parameters passed to the action
     * via #getConfig()
     */
    protected function init()
    {
        $this->config = json_decode($this->action->getWorkflowData(), true);
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


}
 