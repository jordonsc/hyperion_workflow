<?php
namespace Hyperion\Workflow\CommandDriver\Distribution;

use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;

/**
 * Configure auto-scaling groups
 */
class AsgDriver extends AbstractCommandDriver implements CommandDriverInterface
{

    public function execute()
    {
        $elb = $this->getConfig('elb');
    }

}