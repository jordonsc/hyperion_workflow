<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Project;
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

        /** @var DataManager $dbal */
        $dbal = $this->getService('hyperion.dbal');

        $project = new Project();
        $project->setName("Decider test");
        $dbal->create($project);



    }

} 