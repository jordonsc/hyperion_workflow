<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Workflow\Entity\WorkflowCommand;

interface DeciderInterface
{

    /**
     * Get all decided commands
     *
     * @return WorkflowCommand[]
     */
    public function getCommands();

} 