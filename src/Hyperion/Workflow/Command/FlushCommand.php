<?php

namespace Hyperion\Workflow\Command;

use Bravo3\Cache\Redis\RedisCachePool;
use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlushCommand extends ApplicationCommand
{

    protected function configure()
    {
        $this->setName('flush')->setDescription('Flush out all active workflows');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->write("Closing all workflow executions.. ");
        // ..

        $output->write("Flushing workflow cache.. ");

        /** @var RedisCachePool $pool */
        $pool = $this->getService('hyperion.cache_pool');
        $pool->clear();

        $output->writeln("<info>done</info>");

    }

}