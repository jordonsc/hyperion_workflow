<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\BakeStage;
use Hyperion\Workflow\Enum\CommandType;
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
        $spawn_state = $this->getState(self::NS_INSTANCE.'.state', null);
        $instance_ns = $this->getNsPrefix().self::NS_INSTANCE;

        // Haven't created it yet
        if ($spawn_state === null) {
            // Spawn instance
            $this->commands[] = new WorkflowCommand(CommandType::LAUNCH_INSTANCE, [], $instance_ns);
            $this->setState(self::NS_INSTANCE.'.state', InstanceState::PENDING);
            return WorkflowResult::COMMAND();
        }

        // Wait for ready
        switch ($spawn_state) {
            // Waiting for instance to spawn
            default:
            case InstanceState::PENDING:
            case InstanceState::STARTING:
                $this->commands[] = new WorkflowCommand(
                    CommandType::WAIT_CHECK_INSTANCE,
                    ['delay' => self::CHECK_DELAY],
                    $instance_ns);
                return WorkflowResult::COMMAND();

            // Ready - start baking
            case InstanceState::RUNNING:
                $this->commands[] = new WorkflowCommand(CommandType::BAKE_INSTANCE, [], $instance_ns);
                return WorkflowResult::COMMAND();

            // These are all error cases at this stage of the process and shouldn't occur
            case InstanceState::SHUTTING_DOWN:
            case InstanceState::TERMINATING:
            case InstanceState::STOPPED:
            case InstanceState::TERMINATED:
                $this->reason = 'Failed to spawn bake template';
                return WorkflowResult::FAIL();
        }


    }


} 