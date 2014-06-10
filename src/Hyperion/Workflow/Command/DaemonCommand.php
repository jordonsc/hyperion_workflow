<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Use this command to run a worker/decider in an endless loop
 */
class DaemonCommand extends ApplicationCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use AbortTrait;
    use CommandLoggerTrait;

    protected $daemons = ['worker', 'decider'];

    protected function configure()
    {
        $this->configureInput()->setName('daemon')->setDescription('Run a workflow listener as a daemon')
            ->addArgument(
                "daemon",
                InputArgument::REQUIRED,
                "Workflow task to daemonise [".join('|', $this->daemons)."]"
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input);
        $daemon = $input->getArgument('daemon');

        // Check we have a valid workflow command
        if (!in_array($daemon, $this->daemons)) {
            $err = "Unknown daemon: ".$daemon." - options: ".join(', ', $this->daemons);
            $output->writeln("<error>".$err."</error>");
            $this->log(LogLevel::ERROR, $err);
            return;
        }

        $this->log(LogLevel::INFO, "-- Daemon for [".$daemon."] started --");

        // Warn the user if ctrl+c is dangerous (OS/env dependant)
        if ($this->setupAbortIntercepts()) {
            $this->log(LogLevel::INFO, "Use ctrl+c to gracefully abort after polling");
        } else {
            $this->log(
                LogLevel::NOTICE,
                "Signal intercepts not available, cannot gracefully abort - see docs/AbortingWorkflowDaemons.md"
            );
        }

        // Prep the execution command
        $bin = $_SERVER['SCRIPT_NAME'].' run:'.$daemon;
        if ($access = $input->getOption('access')) {
            $bin .= ' -l '.$access;
            if ($error = $input->getOption('error')) {
                $bin .= ' -L '.$error;
            }
        }

        // Loop until ctrl+c hit
        do {
            $output = [];
            $return_var = 0;
            exec($bin, $output, $return_var);

            // 0 for success, 2 for SIGINT caught and ignored until we're done
            if ($return_var !== 0 && $return_var !== 2) {
                $this->log(LogLevel::ERROR, $daemon.": execution error (".$return_var.")", $output);
            }

            $this->checkSignals();
        } while (!$this->abort);

        // $this->abort_signal should always be 2 (SIGINT)
        // We'll keep SIGTSTP (20, invoked by ctrl+z) open so the user can insta-kill if required
        $this->log(LogLevel::INFO, $daemon.": kill signal caught (".$this->abort_signal."), aborting gracefully");
    }

}
