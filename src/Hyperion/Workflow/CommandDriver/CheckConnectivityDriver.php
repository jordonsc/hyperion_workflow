<?php
namespace Hyperion\Workflow\CommandDriver;

use Hyperion\Workflow\CommandDriver\Traits\InstanceReportTrait;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Check a TCP connection can be established
 */
class CheckConnectivityDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use InstanceReportTrait;

    public function execute()
    {
        $address = $this->getConfig('address');
        $port    = $this->getConfig('port');
        $timeout = max(1, (int)$this->getConfig('timeout', 5));

        if (!$address || !$port) {
            throw new CommandFailedException("Require an `address` and `port`");
        }

        // Wait-check option -
        if ($delay = $this->getConfig('delay', 0)) {
            sleep($delay);
        }

        // Try to open a connection
        $errno  = null;
        $errmsg = null;
        $fp     = @fsockopen('tcp://'.$address, $port, $errno, $errmsg, $timeout);

        if ($fp === false) {
            $this->pool->getItem($this->command->getResultNamespace())->set('false');
            $this->pool->getItem($this->command->getResultNamespace().'.code')->set($errno);
            $this->pool->getItem($this->command->getResultNamespace().'.message')->set($errmsg);
        } else {
            fclose($fp);
            $this->pool->getItem($this->command->getResultNamespace())->set('true');
            $this->pool->getItem($this->command->getResultNamespace().'.code')->set('');
            $this->pool->getItem($this->command->getResultNamespace().'.message')->set('');
        }

    }

}
 