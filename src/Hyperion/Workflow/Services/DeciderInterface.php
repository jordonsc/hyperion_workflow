<?php
namespace Hyperion\Workflow\Services;

use Bravo3\Cache\PoolInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\WorkflowResult;

interface DeciderInterface
{

    function __construct(Action $action, PoolInterface $cache, DataManager $dbal);

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