<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Interfaces\Common\ImageInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 * @method null setState($key, $value, $ttl = 3600)
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

        $this->setState($namespace.'.id', $image->getImageId());
        $this->setState($namespace.'.name', $image->getName());
        $this->setState($namespace.'.owner', $image->getOwner());
        $this->setState($namespace.'.state', $image->getState()->value());
    }

}
