<?php

namespace Hyperion\Tests\Workflow;

use Hyperion\Framework\Engine\Application;
use Hyperion\Workflow\Engine\WorkflowApplication;

abstract class WorkflowApplicationTestCase extends \PHPUnit_Framework_TestCase
{
    protected static $app = null;

    /**
     * Get the application object
     *
     * @return WorkflowApplication
     */
    public function getApplication()
    {
        // Reset application
        Application::setAppDir(__DIR__.'/../../../');
        Application::setBundles([]);
        Application::addBundle(new \Hyperion\Workflow\WorkflowBundle());

        if (static::$app === null) {
            static::$app = new WorkflowApplication('test', true);
        }

        return static::$app;
    }

} 