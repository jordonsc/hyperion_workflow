<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Hyperion\Workflow\Services\WorkManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends ApplicationCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use AbortTrait;
    use CommandLoggerTrait;

    protected function configure()
    {
        $this->configureInput()->setName('run:worker')->setDescription('Process a single worker task');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input);
        $this->setupAbortIntercepts();
        $task = null;

        /** @var WorkManager $wm */
        $wm = $this->getService('hyperion.work_manager');
        $wm->setLogger($this->logger);

        try {
            // Poll and process a task
            $this->debug("Polling for work");
            $task = $wm->getWorkTask();

            if ($task) {
                $this->debug(
                    "Found job '".$task->getExecutionId()."' (".$task->getWorkflowName().'/'.
                    $task->getWorkflowVersion().
                    ') for action '.$task->getActionId()
                );

                $wm->processWorkTask($task);
            }

        } catch (\Exception $e) {
            // Error during task processing - log and fail the task
            $this->log(
                LogLevel::CRITICAL,
                "Worker Exception: ".$e->getMessage(),
                [
                    'File: '.$e->getFile(),
                    'Line: '.$e->getLine(),
                    'Code: '.$e->getCode(),
                    'Trace: '.$e->getTraceAsString(),
                ]
            );

            if ($task) {
                try {
                    $wm->respondFailed($task, "Exception: ".$e->getMessage());
                    $this->log(LogLevel::ERROR, "Task '".$task->getExecutionId()."' failed due to internal errors");
                } catch (\Exception $fe) {
                    $this->log(LogLevel::CRITICAL, "Failed to fail task: ".$fe->getMessage());
                }
            }
        }

    }

} 