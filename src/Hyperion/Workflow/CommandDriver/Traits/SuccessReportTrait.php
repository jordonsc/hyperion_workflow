<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 */
trait SuccessReportTrait
{
    protected function reportSuccess()
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $this->pool->getItem($namespace)->set('1');
    }

    protected function reportFailed($reason = '')
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $this->pool->getItem($namespace)->set('0');
        $this->pool->getItem($namespace.'.error')->set($reason);
    }
} 