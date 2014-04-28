<?php


namespace Hyperion\Framework\Tests\Command;


use Hyperion\Tests\Workflow\WorkflowApplicationTestCase;
use Hyperion\Workflow\Command\DeciderCommand;
use Hyperion\Workflow\Command\WorkerCommand;
use Hyperion\Workflow\Engine\WorkflowApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

class DeciderCommandTestWorkflow extends WorkflowApplicationTestCase
{

    /**
     * @small
     */
    public function testWorkerCommand()
    {
        $cmd = 'run:decider';
        $app = $this->getApplication();

        $command = $app->get($cmd);
        $this->assertTrue($command instanceof DeciderCommand);

        $input  = new ArrayInput(['command' => $cmd], $app->getDefinition());
        $output = new BufferedOutput();
        $command->run($input, $output);

        $this->assertContains('I am a decider.', $output->fetch());
    }

}
 