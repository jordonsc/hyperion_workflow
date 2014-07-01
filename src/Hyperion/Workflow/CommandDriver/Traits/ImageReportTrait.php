<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Interfaces\Common\ImageInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 */
trait ImageReportTrait
{

    /**
     * Save image details to the cache pool
     *
     * @param ImageInterface $image
     */
    protected function saveImageReport(ImageInterface $image)
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $this->pool->getItem($namespace.'.id')->set($image->getImageId());
        $this->pool->getItem($namespace.'.name')->set($image->getName());
        $this->pool->getItem($namespace.'.owner')->set($image->getOwner());
        $this->pool->getItem($namespace.'.state')->set($image->getState()->value());
    }

}
