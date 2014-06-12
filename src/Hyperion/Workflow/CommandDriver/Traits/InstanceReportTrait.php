<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Enum\InstanceState as CloudInstanceState;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * @property WorkflowCommand $command
 */
trait InstanceReportTrait
{
    /**
     * @var WorkflowCommand
     */
    protected $command;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * Save an array of instance details to the cache pool
     *
     * @param InstanceInterface[] $instances
     */
    protected function saveAllInstancesReport(array $instances)
    {
        $instance_index = 0;
        foreach ($instances as $instance) {
            $this->saveInstanceReport($instance_index++, $instance);
        }

    }

    /**
     * Save instance details to the cache pool
     *
     * @param int               $index
     * @param InstanceInterface $instance
     */
    protected function saveInstanceReport($index, InstanceInterface $instance)
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        // Instance ID
        $this->pool->getItem($namespace.'.'.$index.'.instance-id')->set(
            $instance->getInstanceId()
        );

        // State
        $state      = $instance->getInstanceState(); // cloud-controller enum
        $dbal_state = null;

        // Create a DBAL state enum
        switch ($state) {
            default:
            case CloudInstanceState::PENDING:
                $dbal_state = InstanceState::PENDING;
                break;
            case CloudInstanceState::STARTING:
                $dbal_state = InstanceState::STARTING;
                break;
            case CloudInstanceState::RUNNING:
                $dbal_state = InstanceState::RUNNING;
                break;
            case CloudInstanceState::STOPPING:
                $dbal_state = InstanceState::SHUTTING_DOWN;
                break;
            case CloudInstanceState::STOPPED:
                $dbal_state = InstanceState::STOPPED;
                break;
            case CloudInstanceState::TERMINATED:
                $dbal_state = InstanceState::TERMINATED;
                break;
        }

        $this->pool->getItem($namespace.'.'.$index.'.state')->set($dbal_state);
    }

}
