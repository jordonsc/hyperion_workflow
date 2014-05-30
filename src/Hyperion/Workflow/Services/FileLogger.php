<?php
namespace Hyperion\Workflow\Services;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class FileLogger extends AbstractLogger
{
    protected $access;
    protected $error;
    protected $eol = "\n";

    function __construct($access, $error = null)
    {
        $this->access = fopen($access, 'a');
        $this->error  = $error ? fopen($error, 'a') : null;

    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $msg = date('c')." [".$level."] ".$message.$this->eol;
        if ($context) {
            $msg .= '<<< Context:'.$this->eol.print_r($context, true).'>>>'.$this->eol;
        }

        if ($this->error) {
            // Access + error logs
            if ($level === LogLevel::INFO || $level === LogLevel::DEBUG) {
                fwrite($this->access, $msg);

            } else {
                fwrite($this->error, $msg);
            }
        } else {
            // Combined logs
            fwrite($this->access, $msg);
        }
    }

    /**
     * Set new-line character
     *
     * @param string $eol
     * @return $this
     */
    public function setEol($eol)
    {
        $this->eol = $eol;
        return $this;
    }

    /**
     * Get new-line character
     *
     * @return string
     */
    public function getEol()
    {
        return $this->eol;
    }

} 