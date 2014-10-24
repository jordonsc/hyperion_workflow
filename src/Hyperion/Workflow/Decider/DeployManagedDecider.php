<?php
namespace Hyperion\Workflow\Decider;

use Bravo3\CloudCtrl\Enum\ImageState;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Enum\BakeStatus;
use Hyperion\Dbal\Enum\DistributionStatus;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ActionPhase;
use Hyperion\Workflow\Enum\BakeStage;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\WorkflowResult;

class DeployManagedDecider extends AbstractDecider implements DeciderInterface
{

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult()
    {
        $this->reason = "Managed deployments are not supported";
        return WorkflowResult::FAIL();
    }
}
