<?php
namespace Hyperion\Workflow\CommandDriver;

use Bravo3\Cache\PoolInterface;
use Bravo3\CloudCtrl\Services\CloudService;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * Interface for all command drivers that will make an API call
 *
 * These drivers should be created and executed by the CommandManager
 */
interface CommandDriverInterface
{

    function __construct(
        WorkflowCommand $command,
        CloudService $service,
        Project $project,
        Environment $environment,
        PoolInterface $pool,
        DataManager $dbal
    );

    public function execute();

}
 