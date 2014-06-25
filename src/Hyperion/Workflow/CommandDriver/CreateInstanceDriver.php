<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\CloudCtrl\Entity\Common\Zone;
use Bravo3\CloudCtrl\Enum\InstanceState as CloudInstanceState;
use Bravo3\CloudCtrl\Interfaces\Instance\InstanceInterface;
use Bravo3\CloudCtrl\Schema\InstanceSchema;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\CommandDriver\Traits\InstanceReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Spawn a new instance
 */
class CreateInstanceDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use InstanceReportTrait;

    public function execute()
    {
        $p     = $this->project;
        $e     = $this->environment;
        $count = $this->getConfig('count', 1);

        $schema = new InstanceSchema();
        $schema->setTemplateImageId($p->getSourceImageId());
        $schema->setTenancy((string)$e->getTenancy());
        $schema->setInstanceSize($e->getInstanceSize());
        $schema->setFirewalls($e->getFirewalls());
        $schema->setTags($e->getTags());
        $schema->setNetwork($e->getNetwork());

        $zones = [];
        foreach ($p->getZones() as $zone) {
            $zones[] = new Zone($zone);
        }
        $schema->setZones($zones);

        $keys = $e->getKeyPairs();
        $schema->setKeyName($keys ? $keys[0] : '');

        // Spawn the instances
        $report = $this->service->getInstanceManager()->createInstances($count, $schema);

        if ($report->getSuccess()) {
            // Success, save details to provided cache pool
            $this->saveAllInstancesReport($report->getInstances());
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
        }

    }

}
 