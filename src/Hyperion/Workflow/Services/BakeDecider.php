<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Dbal\Entity\Action;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\WorkflowResult;
use Hyperion\Workflow\Traits\ConfigTrait;

class BakeDecider implements DeciderInterface
{
    const NS_INSTANCE = 'instance';
    use ConfigTrait;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var WorkflowCommand[]
     */
    protected $commands = [];

    /**
     * @var string
     */
    protected $reason = null;

    function __construct(Action $action)
    {
        $this->action = $action;
    }

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult()
    {
        $this->config = json_decode($this->action->getWorkflowData(), true);

        // Launch instance
        if (!$this->getConfig(self::NS_INSTANCE)) {
            $this->commands[] = new WorkflowCommand(CommandType::LAUNCH_INSTANCE, [], self::NS_INSTANCE);
            return WorkflowResult::COMMAND();
        }

        // Wait for ready

        // Bake

        // Save image

        // Wait for image

        // Terminate instance


        return WorkflowResult::COMPLETE();
    }

    /**
     * Get the reason/message of the result
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Get commands
     *
     * @return WorkflowCommand[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

} 