<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeciderCommand extends ApplicationCommand
{

    protected function configure()
    {
        $this->setName('run:decider')->setDescription('Spawn a decider');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("I am a decider.");

    }

} 