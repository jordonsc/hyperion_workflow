<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

use Bravo3\Cache\PoolInterface;
use Hyperion\Workflow\Entity\WorkflowCommand;

/**
 * @property WorkflowCommand $command
 * @property PoolInterface   $pool
 * @method null setState($key, $value, $ttl = 3600)
 */
trait SuccessReportTrait
{
    protected function reportSuccess()
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $this->setState($namespace, '1');
    }

    protected function reportFailed($reason = '')
    {
        $namespace = $this->command->getResultNamespace();
        if (!$namespace) {
            return;
        }

        $this->setState($namespace, '0');
        $this->setState($namespace.'.error', $reason);
    }
} 