<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\BakeStage;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\ApplicationEnvironment;
use Hyperion\Workflow\Enum\WorkflowResult;

class BakeDecider extends AbstractDecider implements DeciderInterface
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
        $this->init();

        // State information
        $bake_stage = $this->getState(self::NS_STAGE, BakeStage::SPAWNING);

        switch ($bake_stage) {
            // Launch instance
            default:
            case BakeStage::SPAWNING:
                return $this->processSpawning();
            // Bake
            // Save image
            // Wait for image
            // Terminate instance
        }
    }


    /**
     * Sequence of events that happen while the bake template is spawning
     *
     * @return WorkflowResult
     */
    protected function processSpawning()
    {
        $instance_state = $this->getState(self::NS_INSTANCE.'.0.state', null);

        // Haven't created it yet
        if ($instance_state === null) {
            return $this->actionSpawnInstance();
        }

        // Wait for ready
        switch ($instance_state) {
            // Waiting for instance to spawn
            case InstanceState::PENDING:
            case InstanceState::STARTING:
                return $this->actionCheckInstance();

            // Ready - start baking
            case InstanceState::RUNNING:
                return $this->actionBakeInstance();

            // These are all error cases at this stage of the process and shouldn't occur
            default:
            case InstanceState::SHUTTING_DOWN:
            case InstanceState::TERMINATING:
            case InstanceState::STOPPED:
            case InstanceState::TERMINATED:
                $this->reason = 'Failed to spawn bake template';
                return WorkflowResult::FAIL();
        }

    }

    /**
     * Spawn a new bakery instance
     *
     * @return WorkflowResult
     */
    protected function actionSpawnInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action->getProject(),
            ApplicationEnvironment::BAKERY,
            CommandType::LAUNCH_INSTANCE,
            [],
            $this->getNsPrefix().self::NS_INSTANCE
        );
        $this->setState(self::NS_INSTANCE.'.0.state', InstanceState::PENDING);
        return WorkflowResult::COMMAND();
    }

    /**
     * Check an instance
     *
     * @return WorkflowResult
     */
    protected function actionCheckInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action->getProject(),
            ApplicationEnvironment::BAKERY,
            CommandType::WAIT_CHECK_INSTANCE,
            [
                'delay'       => self::CHECK_DELAY,
                'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id', null),
            ],
            $this->getNsPrefix().self::NS_INSTANCE
        );
        return WorkflowResult::COMMAND();
    }

    /**
     * Kick off the bakery process
     *
     * @return WorkflowResult
     */
    protected function actionBakeInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action->getProject(),
            ApplicationEnvironment::BAKERY,
            CommandType::BAKE_INSTANCE,
            [
                'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id', null),
            ],
            $this->getNsPrefix().self::NS_INSTANCE
        );

        $this->setState(self::NS_STAGE, BakeStage::BAKING);

        return WorkflowResult::COMMAND();
    }

}
