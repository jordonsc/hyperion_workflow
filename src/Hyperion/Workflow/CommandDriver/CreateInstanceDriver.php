<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\CloudCtrl\Entity\Common\Zone;
use Bravo3\CloudCtrl\Enum\InstanceState as CloudInstanceState;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Bravo3\CloudCtrl\Schema\InstanceSchema;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Spawn a new instance
 */
class CreateInstanceDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    public function execute()
    {
        $p     = $this->project;
        $count = $this->getConfig('count', 1);

        $schema = new InstanceSchema();
        $schema->setTemplateImageId($p->getSourceImageId());
        $schema->setTenancy($p->getTenancy());
        $schema->setInstanceSize($this->isProd() ? $p->getInstanceSizeProd() : $p->getInstanceSizeTest());
        $schema->setFirewalls($this->isProd() ? $p->getFirewallsProd() : $p->getFirewallsTest());
        $schema->setTags($this->isProd() ? $p->getTagsProd() : $p->getTagsTest());
        $schema->setNetwork($this->isProd() ? $p->getNetworkProd() : $p->getNetworkTest());

        $zones = [];
        foreach ($p->getZones() as $zone) {
            $zones[] = new Zone($zone);
        }
        $schema->setZones($zones);

        $keys = $this->isProd() ? $p->getKeysProd() : $p->getKeysTest();
        $schema->setKeyName($keys ? $keys[0] : '');

        // Spawn the instances
        $report = $this->service->getInstanceManager()->createInstances($count, $schema);

        if ($report->getSuccess()) {
            // Success, save details to provided cache pool
            if ($this->command->getResultNamespace()) {
                $instance_index = 0;
                foreach ($report->getInstances() as $instance) {
                    $this->saveInstanceReport($instance_index++, $instance);
                }
            }

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
 