<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\WorkflowResult;

interface DeciderInterface
{

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult();

    /**
     * Get the reason/message of the result
     *
     * @return string
     */
    public function getReason();

    /**
     * Get all decided commands, should the result be WorkflowResult::COMMAND()
     *
     * @return WorkflowCommand[]
     */
    public function getCommands();

} 