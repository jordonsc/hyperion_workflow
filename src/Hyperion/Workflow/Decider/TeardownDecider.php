<?php
namespace Hyperion\Workflow\Decider;

use Hyperion\Dbal\Entity\Distribution;
use Hyperion\Dbal\Entity\Instance;
use Hyperion\Dbal\Enum\DistributionStatus;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ActionPhase;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\TeardownStage;
use Hyperion\Workflow\Enum\WorkflowResult;
use Hyperion\Workflow\Exception\UnexpectedValueException;

class TeardownDecider extends AbstractDecider implements DeciderInterface
{
    const NS_STAGE    = 'stage';
    const NS_INSTANCE = 'instance';
    const CHECK_DELAY = 5;

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult()
    {
        // State information
        $stage = $this->getState(self::NS_STAGE, TeardownStage::TERMINATING);

        switch ($stage) {
            // Launch instance, bake
            case TeardownStage::TERMINATING:
                return $this->terminateInstances();
            // Shutdown instance
            case TeardownStage::WAIT_FOR_COMPLETION:
                return $this->waitForCompletion();

            // Default
            default:
                $this->reason = "Workflow default at stage '".$stage."'";
                return WorkflowResult::FAIL();
        }
    }

    /**
     * Shutdown the bakery instance
     *
     * @return WorkflowResult
     */
    protected function terminateInstances()
    {
        /** @var Distribution $distro */
        $distro = $this->dbal->retrieve(Entity::DISTRIBUTION(), $this->action->getDistribution());
        if (!$distro) {
            throw new UnexpectedValueException("Unknown distribution ID (".$this->action->getDistribution().")");
        }

        $distro->setStatus(DistributionStatus::TERMINATING());
        $this->dbal->update($distro);

        $instances = $this->dbal->getRelatedEntities($distro, Entity::INSTANCE());
        $this->setState(self::NS_INSTANCE.'.count', $instances->count());

        // Check there is actually something to do
        if ($instances->count() == 0) {
            return WorkflowResult::COMPLETE();
        }

        /** @var Instance $instance */
        foreach ($instances as $index => $instance) {
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::TERMINATE_INSTANCE,
                [
                    'instance-id'    => $instance->getInstanceId(),
                    'ignore-failure' => 1,  // in-case instance has already been terminated
                ],
                $this->getNsPrefix().self::NS_INSTANCE.'.'.$index
            );
        }
        $this->setState(self::NS_STAGE, TeardownStage::WAIT_FOR_COMPLETION);
        $this->progress(ActionPhase::TERMINATING);
        return WorkflowResult::COMMAND();
    }

    /**
     * Check an instance
     *
     * @return WorkflowResult
     */
    protected function waitForCompletion()
    {
        $count = $this->getState(self::NS_INSTANCE.'.count', 0);
        for ($i = 0; $i < $count; $i++) {
            if ($this->getState(self::NS_INSTANCE.'.'.$i, '-') == '-') {
                return WorkflowResult::COMMAND();
            }
        }
        return WorkflowResult::COMPLETE();
    }

    /**
     * Update the distribution status
     *
     * @param DistributionStatus $status
     * @return bool
     */
    protected function setDistroStatus(DistributionStatus $status)
    {
        /** @var Distribution $distro */
        $distro = $this->dbal->retrieve(Entity::DISTRIBUTION(), $this->action->getDistribution());
        if (!$distro) {
            return false;
        }
        $distro->setStatus($status);
        $this->dbal->update($distro);
        return true;
    }

    /**
     * Called by the DecisionManager when the workflow completes
     */
    public function onComplete()
    {
        $this->setDistroStatus(DistributionStatus::TERMINATED());
    }

    /**
     * Called by the DecisionManager when the workflow fails
     */
    public function onFail()
    {
        $this->setDistroStatus(DistributionStatus::FAILED());
    }


}
