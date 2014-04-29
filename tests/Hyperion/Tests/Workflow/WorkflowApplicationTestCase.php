<?php

namespace Hyperion\Tests\Workflow;

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
        require(__DIR__.'/../../../../app/app.php');

        if (static::$app === null) {
            static::$app = new WorkflowApplication('test', true);
        }

        return static::$app;
    }

} 