<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\CloudCtrl\Entity\Common\Zone;
use Bravo3\CloudCtrl\Enum\InstanceState as CloudInstanceState;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Bravo3\CloudCtrl\Schema\InstanceSchema;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\CommandDriver\Traits\InstanceReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Spawn a new instance
 */
class CreateInstanceDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use InstanceReportTrait;

    public function execute()
    {
        $p     = $this->project;
        $e     = $this->environment;
        $count = $this->getConfig('count', 1);

        $schema = new InstanceSchema();
        $schema->setTemplateImageId($p->getSourceImageId());
        $schema->setTenancy((string)$e->getTenancy());
        $schema->setInstanceSize($e->getInstanceSize());
        $schema->setFirewalls($e->getFirewalls());
        $schema->setTags($e->getTags());
        $schema->setNetwork($e->getNetwork());

        $zones = [];
        foreach ($p->getZones() as $zone) {
            $zones[] = new Zone($zone);
        }
        $schema->setZones($zones);

        $keys = $e->getKeyPairs();
        $schema->setKeyName($keys ? $keys[0] : '');

        // Spawn the instances
        $report = $this->service->getInstanceManager()->createInstances($count, $schema);

        if ($report->getSuccess()) {
            // Success, save details to provided cache pool
            $this->saveAllInstancesReport($report->getInstances());
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
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
 