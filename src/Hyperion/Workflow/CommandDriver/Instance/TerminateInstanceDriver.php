<?php
namespace Hyperion\Workflow\CommandDriver\Instance;

use Bravo3\CloudCtrl\Filters\IdFilter;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Traits\SuccessReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Terminate (delete) an instance
 */
class TerminateInstanceDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use SuccessReportTrait;

    public function execute()
    {
        $instance_id = $this->getConfig('instance-id');
        if (!$instance_id) {
            throw new CommandFailedException("No instance ID");
        }

        $instanceFilter = new IdFilter();
        $instanceFilter->addId($instance_id);

        // Spawn the instances
        $report = $this->service->getInstanceManager()->terminateInstances($instanceFilter);

        if ($report->getSuccess()) {
            $this->reportSuccess();
        } else {
            $this->reportFailed($report->getResultMessage());
            throw new CommandFailedException($report->getResultMessage());
        }

    }

}
 