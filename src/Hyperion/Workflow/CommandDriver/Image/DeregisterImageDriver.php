<?php
namespace Hyperion\Workflow\CommandDriver\Image;

use Bravo3\CloudCtrl\Schema\ImageSchema;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Traits\SuccessReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Deregister a machine image
 */
class DeregisterImageDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use SuccessReportTrait;

    public function execute()
    {
        $image_id = $this->getConfig('image-id');

        if (!$image_id) {
            throw new CommandFailedException("Image ID missing");
        }

        $report = $this->service->getInstanceManager()->deregisterImage($image_id);

        if ($report->getSuccess()) {
            $this->reportSuccess();
        } else {
            $this->reportFailed($report->getResultMessage());
            throw new CommandFailedException($report->getResultMessage());
        }
    }

}
 