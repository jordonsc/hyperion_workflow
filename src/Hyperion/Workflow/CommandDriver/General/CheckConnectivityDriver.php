<?php
namespace Hyperion\Workflow\CommandDriver\General;

use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
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
        $timeout = max(1, (int)$this->getConfig('timeout', 5));

        if (!$address) {
            throw new CommandFailedException("Require an `address` and `port`");
        }

        // Wait-check option -
        if ($delay = $this->getConfig('delay', 0)) {
            sleep($delay);
        }

        // Try to open a connection
        $errno  = null;
        $errmsg = null;
        $fp     = @fsockopen('tcp://'.$address, $this->environment->getSshPort(), $errno, $errmsg, $timeout);

        if ($fp === false) {
            $this->setState($this->command->getResultNamespace(), 'false');
            $this->setState($this->command->getResultNamespace().'.code', $errno);
            $this->setState($this->command->getResultNamespace().'.message', $errmsg);
        } else {
            fclose($fp);
            $this->setState($this->command->getResultNamespace(), 'true');
            $this->setState($this->command->getResultNamespace().'.code', '');
            $this->setState($this->command->getResultNamespace().'.message', '');
        }

    }

}
 