<?php


namespace Hyperion\Framework\Tests\Command;


use Hyperion\Tests\Workflow\WorkflowApplicationTestCase;
use Hyperion\Workflow\Command\WorkerCommand;
use Hyperion\Workflow\Engine\WorkflowApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

class WorkerCommandTestWorkflow extends WorkflowApplicationTestCase
{

    /**
     * @small
     */
    public function testWorkerCommand()
    {
        $cmd = 'run:worker';
        $app = $this->getApplication();

        $command = $app->get($cmd);
        $this->assertTrue($command instanceof WorkerCommand);

        $input  = new ArrayInput(['command' => $cmd], $app->getDefinition());
        $output = new BufferedOutput();
        $command->run($input, $output);

        $this->assertContains('I am a worker.', $output->fetch());
    }

}
 