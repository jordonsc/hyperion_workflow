<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Hyperion\Workflow\Services\DecisionManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeciderCommand extends ApplicationCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use AbortTrait;
    use CommandLoggerTrait;

    protected function configure()
    {
        $this->configureInput()->setName('run:decider')->setDescription('Process a single decision task');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input);
        $this->setupAbortIntercepts();
        $task = null;

        /** @var DecisionManager $dm */
        $dm = $this->getService('hyperion.decision_manager');
        $dm->setLogger($this->logger);

        try {
            // Poll and process a task
            $this->debug("Polling for decision");
            $task = $dm->getDecisionTask();

            if ($task) {
                $this->debug(
                    "Found decision '".$task->getExecutionId()."' (".$task->getWorkflowName().'/'.
                    $task->getWorkflowVersion().
                    ') for action '.$task->getActionId()
                );

                $dm->processDecisionTask($task);
            }

        } catch (\Exception $e) {
            // Error during task processing - log and fail the task
            $this->log(
                LogLevel::CRITICAL,
                "Decider exception: ".$e->getMessage(),
                [
                    'Exception: '.get_class($e),
                    'File: '.$e->getFile(),
                    'Line: '.$e->getLine(),
                    'Code: '.$e->getCode(),
                ]
            );

            if ($task) {
                try {
                    $dm->respondFailed($task, "Exception: ".$e->getMessage());
                    $this->log(LogLevel::ERROR, "Task '".$task->getExecutionId()."' failed due to internal errors");
                } catch (\Exception $fe) {
                    $this->log(LogLevel::CRITICAL, "Failed to fail task: ".$fe->getMessage());
                }
            }
        }

    }

}