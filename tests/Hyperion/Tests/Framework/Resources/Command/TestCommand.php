<?php


namespace Hyperion\Tests\Framework\Resources\Command;


use Hyperion\Framework\Command\ApplicationCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ApplicationCommand
{

    protected function configure()
    {
        $this->setName('hyperion:framework:test:command')->setDescription('Test command');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }

    public function testPropertyAbstraction()
    {
        return $this->getProperty('test')['integer'];
    }

    public function testServiceAbstraction()
    {
        return $this->getService('test.service');
    }

}

