<?php
namespace Hyperion\Workflow\CommandDriver\Instance;

use Bravo3\CloudCtrl\Entity\Common\Zone;
use Bravo3\CloudCtrl\Schema\InstanceSchema;
use Hyperion\Dbal\Enum\EnvironmentType;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
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
        if ($e->getEnvironmentType() == EnvironmentType::BAKERY()) {
            $schema->setTemplateImageId($p->getSourceImageId());
        } else {
            $schema->setTemplateImageId($p->getBakedImageId());
        }
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
            $this->initAction();
            $this->saveAllInstancesReport($report->getInstances(), $this->action->getDistribution());
        } else {
            // Failed :(
            throw new CommandFailedException($report->getResultMessage());
        }

    }

}
 