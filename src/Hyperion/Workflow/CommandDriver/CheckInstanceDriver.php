<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\CloudCtrl\Filters\InstanceFilter;
use Hyperion\Workflow\CommandDriver\Traits\InstanceReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Check the status of an instance
 */
class CheckInstanceDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use InstanceReportTrait;

    public function execute()
    {
        $instance_id = $this->getConfig('instance-id');
        if (!$instance_id) {
            throw new CommandFailedException("No instance ID");
        }

        // Wait-check option -
        if ($delay = $this->getConfig('delay', 0)) {
            sleep($delay);
        }

        // Spawn the instances
        $instances = new InstanceFilter();
        $instances->addId($instance_id);
        $report = $this->service->getInstanceManager()->describeInstances($instances);

        if ($report->getSuccess()) {
            // Success, save details to provided cache pool
            $this->saveAllInstancesReport($report->getInstances());
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
        }

    }

}
 