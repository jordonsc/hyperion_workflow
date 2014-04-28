<?php

/** @var $loader \Composer\Autoload\ClassLoader */
$loader = require_once(__DIR__.'/../vendor/autoload.php');

use Hyperion\Framework\Engine\Application;

Application::setAppDir(__DIR__);
Application::addBundle(new \Hyperion\Workflow\WorkflowBundle());
