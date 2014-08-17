<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Collections\InstanceCollection;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Hyperion\Dbal\Collection\CriteriaCollection;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Instance;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Mappers\InstanceStateMapper;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 * @property DataManager     $dbal
 * @method null setState($key, $value, $ttl = 3600)
 */
trait InstanceReportTrait
{

    /**
     * Save an array of instance details to the cache pool
     *
     * @param InstanceCollection $instances
     */
    protected function saveAllInstancesReport(InstanceCollection $instances, $distribution = null)
    {
        $instance_index = 0;
        foreach ($instances as $instance) {
            $this->saveInstanceReport($instance_index++, $instance, $distribution);
        }
    }

    /**
     * Save instance details to the cache pool
     *
     * @param int               $index
     * @param InstanceInterface $instance
     */
    protected function saveInstanceReport($index, InstanceInterface $instance, $distribution = null)
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $col = new CriteriaCollection();
        $col->add('instance_id', $instance->getInstanceId());
        $found = $this->dbal->search(Entity::INSTANCE(), $col);

        if ($found->count() == 1) {
            $dbal_instance = $found->current();
        } else {
            $dbal_instance = new Instance();
        }

        if ($distribution) {
            $dbal_instance->setDistribution($distribution);
        }

        $dbal_instance->setInstanceId($instance->getInstanceId());
        $this->setState($namespace.'.'.$index.'.instance-id', $instance->getInstanceId());
        $this->setState($namespace.'.'.$index.'.image-id', $instance->getImageId());
        $this->setState($namespace.'.'.$index.'.architecture', $instance->getArchitecture());
        $this->setState($namespace.'.'.$index.'.size', $instance->getInstanceSize());
        $this->setState($namespace.'.'.$index.'.zone', $instance->getZone()->getZoneName());
        $this->setState($namespace.'.'.$index.'.tags', json_encode($instance->getTags()));

        if ($private_ip = $instance->getPrivateAddress()) {
            $dbal_instance->setPrivateDns($private_ip->getDnsName());
            $dbal_instance->setPrivateIp4($private_ip->getIp4Address());
            $dbal_instance->setPrivateIp6($private_ip->getIp6Address());
            $this->setState($namespace.'.'.$index.'.ip.private.dns', $private_ip->getDnsName());
            $this->setState($namespace.'.'.$index.'.ip.private.ip4', $private_ip->getIp4Address());
            $this->setState($namespace.'.'.$index.'.ip.private.ip6', $private_ip->getIp4Address());
        } else {
            $this->setState($namespace.'.'.$index.'.ip.private.dns', '');
            $this->setState($namespace.'.'.$index.'.ip.private.ip4', '');
            $this->setState($namespace.'.'.$index.'.ip.private.ip6', '');
        }

        if ($public_ip = $instance->getPublicAddress()) {
            $dbal_instance->setPublicDns($public_ip->getDnsName());
            $dbal_instance->setPublicIp4($public_ip->getIp4Address());
            $dbal_instance->setPublicIp6($public_ip->getIp6Address());
            $this->setState($namespace.'.'.$index.'.ip.public.dns', $public_ip->getDnsName());
            $this->setState($namespace.'.'.$index.'.ip.public.ip4', $public_ip->getIp4Address());
            $this->setState($namespace.'.'.$index.'.ip.public.ip6', $public_ip->getIp4Address());
        } else {
            $this->setState($namespace.'.'.$index.'.ip.public.dns', '');
            $this->setState($namespace.'.'.$index.'.ip.public.ip4', '');
            $this->setState($namespace.'.'.$index.'.ip.public.ip6', '');
        }

        // State
        $state = InstanceStateMapper::CloudCtrlToDbal($instance->getInstanceState());
        $this->setState($namespace.'.'.$index.'.state', $state->value());

        // Save instance to distribution
        if ($found->count() == 1) {
            $this->dbal->update($dbal_instance);
        } else {
            $this->dbal->create($dbal_instance);
        }

    }

}
