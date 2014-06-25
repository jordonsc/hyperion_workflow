<?php
namespace Hyperion\Workflow\CommandDriver;

class BakeDriver extends AbstractCommandDriver implements CommandDriverInterface
{

    public function execute()
    {
        $prj         = $this->project;
        $env         = $this->environment;
        $instance_id = $this->getConfig('instance-id');

        // do the bakey bakey


    }

}
