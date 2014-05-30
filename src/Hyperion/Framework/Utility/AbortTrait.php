<?php
namespace Hyperion\Framework\Utility;

/**
 * Creates an abort flag for CTRL+C intercepts via pcntl_signal
 *
 * To use this, you will need `pcntl_signal` and `pcntl_signal_dispatch` removed from the php.ini's `disable_functions`
 * directive.
 */
trait AbortTrait
{
    private $pcnlt_intercept_avail = false;
    protected $abort = false;
    protected $abort_signal = null;

    protected function setupAbortIntercepts()
    {
        $disabled = explode(',', ini_get('disable_functions'));
        if (!function_exists('pcntl_signal') ||
            !function_exists('pcntl_signal_dispatch') ||
            in_array('pcntl_signal', $disabled) ||
            in_array('pcntl_signal_dispatch', $disabled)
        ) {
            return false;
        } else {
            // Permit checkSignals() to run
            $this->pcnlt_intercept_avail = true;
        }

        pcntl_signal(
            SIGINT,
            function ($signal) {
                $this->abort = true;
                $this->abort_signal = $signal;
            }
        );

        $this->checkSignals();
        return true;
    }

    protected function checkSignals()
    {
        if ($this->pcnlt_intercept_avail) {
            pcntl_signal_dispatch();
        }
    }
}
