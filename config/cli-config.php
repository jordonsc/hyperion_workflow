<?php

require_once(__DIR__.'/../app/bootstrap.php');

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Hyperion\Workflow\Engine\WorkflowApplication;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), 'dev');
$debug = !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

$application = new WorkflowApplication($env, $debug);
$em = $application->getService('hyperion.data.entity_manager');

return ConsoleRunner::createHelperSet($em);
