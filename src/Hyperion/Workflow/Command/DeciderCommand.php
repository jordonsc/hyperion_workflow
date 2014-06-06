<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Hyperion\Workflow\Entity\WorkflowTask;
use Hyperion\Workflow\Services\WorkflowManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
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

        /** @var WorkflowManager $wfm */
        $wfm = $this->getService('hyperion.workflow_manager');

        $this->debug("Polling for decision");
        $task = $wfm->getDecisionTask();

        if ($task) {
            $this->debug(
                "Found task '".$task->getExecutionId()."' (".$task->getWorkflowName().'/'.$task->getWorkflowVersion().
                ') for action '.$task->getActionId()
            );
        }


    }

}