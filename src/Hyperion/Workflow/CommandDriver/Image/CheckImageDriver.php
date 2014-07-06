<?php
namespace Hyperion\Workflow\CommandDriver\Image;

use Bravo3\CloudCtrl\Enum\ImageState;
use Bravo3\CloudCtrl\Filters\ImageFilter;
use Bravo3\CloudCtrl\Schema\ImageSchema;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Image\Resources\GenericImage;
use Hyperion\Workflow\CommandDriver\Traits\ImageReportTrait;
use Hyperion\Workflow\Enum\ActionPhase;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Report on the status of an image
 */
class CheckImageDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use ImageReportTrait;

    public function execute()
    {
        $image_id = $this->getConfig('image-id');

        if (!$image_id) {
            throw new CommandFailedException("Image ID missing");
        }

        // Wait-check option -
        if ($delay = $this->getConfig('delay', 0)) {
            sleep($delay);
        }

        $filter = new ImageFilter();
        $filter->addId($image_id);

        // Create the image
        $report = $this->service->getInstanceManager()->describeImages($filter);

        if ($report->getSuccess() && ($report->getImages()->count() > 0)) {
            // Success, save image ID
            $this->saveImageReport($report->getImages()->toArray()[0]);
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
        }
    }

}
 