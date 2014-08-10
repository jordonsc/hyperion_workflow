<?php

namespace Hyperion\Workflow\Command;

use Hyperion\Framework\Command\ApplicationCommand;
use Hyperion\Framework\Utility\AbortTrait;
use Hyperion\Framework\Utility\CommandLoggerTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommand extends ApplicationCommand
{

    protected function configure()
    {
        $this->setName('install')->setDescription('Install system services (1 decider, 1 worker)');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (trim(`whoami`) != 'root') {
            $output->writeln("<error>You must run this as root</error>");
            return;
        }

        $docs = realpath(__DIR__.'/../../../../docs/Upstart');
        $bin = realpath(__DIR__.'/../../../..').'/hyperiond';

        $filesystem = new Filesystem();
        $filesystem->copy($docs.'/hyperion-decider.conf', '/etc/init/hyperion-decider.conf', true);
        $filesystem->copy($docs.'/hyperion-worker.conf', '/etc/init/hyperion-worker.conf', true);
        $output->writeln("Upstart scripts created");

        $filesystem->symlink($bin, '/usr/bin/hyperiond');
        $output->writeln("Link to <comment>hyperiond</comment> created");

        $output->writeln('<comment>'.exec('start hyperion-decider').'</comment>');
        $output->writeln('<comment>'.exec('start hyperion-worker').'</comment>');
        $output->writeln("Services started");

        $output->writeln("<info>Install complete</info>");
    }

}