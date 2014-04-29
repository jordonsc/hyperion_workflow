<?php

use Hyperion\Database\Bundle\HyperionDataBundle;
use Hyperion\Framework\Engine\Application;
use Hyperion\Workflow\Bundle\WorkflowBundle;

Application::setAppDir(__DIR__);
Application::addBundle(new WorkflowBundle());
Application::addBundle(new HyperionDataBundle());
