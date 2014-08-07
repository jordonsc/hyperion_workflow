<?php
namespace Hyperion\Framework\Utility;

use Hyperion\Framework\Command\ApplicationCommand;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait CommandLoggerTrait
{

    /**
     * Use the command input to initialise the logger
     *
     * @param InputInterface $input
     */
    protected function initLogger(InputInterface $input)
    {
        if (!method_exists($this, 'setLogger')) {
            return;
        }

        $access = $input->getOption('access');
        $error  = $input->getOption('error');

        if ($access) {
            if ($error) {
                $this->setLogger(new FileLogger($access, $error));
            } else {
                $this->setLogger(new FileLogger($access));
            }
        } else {
            $this->setLogger(new NullLogger());
        }

    }

    /**
     * Adds options to the command input
     *
     * @return $this
     */
    protected function configureInput()
    {
        if (!method_exists($this, 'addOption')) {
            return $this;
        }

        $this->addOption("access", "l", InputOption::VALUE_REQUIRED, "Access log file")
            ->addOption("error", "L", InputOption::VALUE_REQUIRED, "Error log file, uses the access log if omitted");

        return $this;
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    protected function log($level, $message, array $context = [])
    {
        if (!isset($this->logger)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

    /**
     * Output a debug message if debugging is enabled
     *
     * @param string $msg
     * @param array  $context
     */
    protected function debug($msg, array $context = [])
    {
        if (!($this instanceof ApplicationCommand)) {
            return;
        }

        if ($this->getApplication()->getDebug()) {
            // This check isn't needed for anything other than PhpStorm realising the function has to exist
            if (method_exists($this, 'log')) {
                $this->log(LogLevel::DEBUG, $msg, $context);
            }
        }
    }

} 