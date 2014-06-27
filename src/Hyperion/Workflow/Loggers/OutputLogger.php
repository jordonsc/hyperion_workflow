<?php
namespace Hyperion\Workflow\Loggers;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class OutputLogger extends AbstractLogger implements LoggerInterface
{
    protected $fp;

    public function __construct($fn)
    {
        $this->fp = fopen($fn, 'a');
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
        switch ($level) {
            default:
            case LogLevel::INFO:
                break;
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::EMERGENCY:
            case LogLevel::ERROR:
                $message = "\033[31m".$message."\033[0m";
                break;
            case LogLevel::DEBUG:
                $message = "\033[34m".$message."\033[0m";
                break;
            case LogLevel::NOTICE:
                $message = "\033[33m".$message."\033[0m";
                break;
        }

        fwrite($this->fp, $message."\n");
    }

    function __destruct()
    {
        fclose($this->fp);
    }

}
