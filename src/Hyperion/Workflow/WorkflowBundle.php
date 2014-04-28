<?php


namespace Hyperion\Workflow;


use Hyperion\Framework\Engine\Application;
use Hyperion\Framework\Engine\BundleInterface;
use Hyperion\Workflow\Command\DeciderCommand;
use Hyperion\Workflow\Command\WorkerCommand;

class WorkflowBundle implements BundleInterface
{
    public function init(Application &$application)
    {
        $application->importConfig(__DIR__.'/Resources/config.yml');
        $application->add(new DeciderCommand());
        $application->add(new WorkerCommand());
    }
} 