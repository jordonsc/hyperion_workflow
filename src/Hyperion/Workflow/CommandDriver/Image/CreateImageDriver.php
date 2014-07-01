<?php
namespace Hyperion\Workflow\CommandDriver\Image;

use Bravo3\CloudCtrl\Enum\ImageState;
use Bravo3\CloudCtrl\Schema\ImageSchema;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Image\Resources\GenericImage;
use Hyperion\Workflow\CommandDriver\Traits\ImageReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Create a new machine image
 */
class CreateImageDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use ImageReportTrait;

    public function execute()
    {
        $instance_id = $this->getConfig('instance-id');
        $image_name = $this->getConfig('image-name', '');

        if (!$instance_id) {
            throw new CommandFailedException("Instance ID missing");
        }

        // Image schema
        $schema = new ImageSchema($image_name);
        // TODO: storage volumes, etc

        // Create the image
        $report = $this->service->getInstanceManager()->createImage($instance_id, $schema);

        if ($report->getSuccess()) {
            // Success, save image ID
            $image = new GenericImage();
            $image->setState(ImageState::PENDING());
            $image->setName($image_name);
            $image->setImageId($report->getImageId());
            $this->saveImageReport($image);
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
        }
    }

}
 