<?php
namespace Hyperion\Workflow\Loggers;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class MemoryLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var bool
     */
    protected $use_prefix;

    /**
     * @var string
     */
    protected $log = '';

    public function __construct($use_prefix = true)
    {
        $this->use_prefix = $use_prefix;
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
        $message = $this->normaliseText($message);

        if (!$this->use_prefix) {
            $this->log .= $message;
            return;
        }

        switch ($level) {
            case LogLevel::DEBUG:
                $prefix = '? ';
                break;
            default:
            case LogLevel::INFO:
                $prefix = '| ';
                break;
            case LogLevel::NOTICE:
                $prefix = 'i ';
                break;
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::EMERGENCY:
            case LogLevel::ERROR:
                $prefix = '* ';
                break;
        }

        $message = str_replace("\n", "\n".$prefix, $message);

        $this->log .= $prefix.$message."\n";
    }

    /**
     * Returns a new string as if backspace characters had actually deleted their preceding character
     *
     * @param $txt
     * @return string
     */
    protected function normaliseText($txt) {
        $out = '';
        $txt = str_replace("\r\n", "\n", $txt);

        $data_length = strlen($txt);
        $cr = chr(13);
        $bs = chr(8);

        for ($i = 0; $i < $data_length; $i++) {
            $c = $txt{$i};

            if ($c === $cr) {
                // Carriage return, delete until new-line
                $nl_pos = strrpos($out, "\n");
                if ($nl_pos === false) {
                    $out = '';
                } else {
                    $out = substr($out, 0, $nl_pos + 1);
                }
            } elseif ($c === $bs) {
                // Backspace char, read-ahead to see if we can bulk-delete
                $bs_len = 1;
                $bs_pos = $i;
                while (++$bs_pos < $data_length) {
                    if ($txt{$bs_pos} === $bs) {
                        $bs_len++;
                    } else {
                        break;
                    }
                }

                $out = substr($out, 0, -$bs_len);
                $i += ($bs_len - 1);
            } else {
                // Normal char
                $out .= $c;
            }
        }

        return $out;
    }

    /**
     * Get Log
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }


}
