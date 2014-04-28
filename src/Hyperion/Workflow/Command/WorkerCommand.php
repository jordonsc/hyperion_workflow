<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends ApplicationCommand
{

    protected function configure()
    {
        $this->setName('run:worker')->setDescription('Spawn a worker');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("I am a worker.");

    }

} 