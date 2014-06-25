<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Collections\InstanceCollection;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Mappers\InstanceStateMapper;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 */
trait InstanceReportTrait
{

    /**
     * Save an array of instance details to the cache pool
     *
     * @param InstanceCollection $instances
     */
    protected function saveAllInstancesReport(InstanceCollection $instances)
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

        $this->pool->getItem($namespace.'.'.$index.'.instance-id')->set($instance->getInstanceId());
        $this->pool->getItem($namespace.'.'.$index.'.image-id')->set($instance->getImageId());
        $this->pool->getItem($namespace.'.'.$index.'.architecture')->set($instance->getArchitecture());
        $this->pool->getItem($namespace.'.'.$index.'.size')->set($instance->getInstanceSize());
        $this->pool->getItem($namespace.'.'.$index.'.zone')->set($instance->getZone()->getZoneName());
        $this->pool->getItem($namespace.'.'.$index.'.tags')->set(json_encode($instance->getTags()));

        if ($private_ip = $instance->getPrivateAddress()) {
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.dns')->set($private_ip->getDnsName());
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.ip4')->set($private_ip->getIp4Address());
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.ip6')->set($private_ip->getIp4Address());
        } else {
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.dns')->set('');
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.ip4')->set('');
            $this->pool->getItem($namespace.'.'.$index.'.ip.private.ip6')->set('');
        }

        if ($public_ip = $instance->getPublicAddress()) {
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.dns')->set($public_ip->getDnsName());
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.ip4')->set($public_ip->getIp4Address());
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.ip6')->set($public_ip->getIp4Address());
        } else {
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.dns')->set('');
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.ip4')->set('');
            $this->pool->getItem($namespace.'.'.$index.'.ip.public.ip6')->set('');
        }

        // State
        $state = InstanceStateMapper::CloudCtrlToDbal($instance->getInstanceState());
        $this->pool->getItem($namespace.'.'.$index.'.state')->set($state->value());

    }

}
