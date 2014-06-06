<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Dbal\Entity\Action;
use Hyperion\Workflow\Entity\WorkflowCommand;

class BakeDecider implements DeciderInterface
{

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var WorkflowCommand[]
     */
    protected $commands = [];

    function __construct(Action $action)
    {
        $this->action = $action;
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