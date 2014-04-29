<?php

namespace Hyperion\Tests\Framework;

use Hyperion\Framework\Engine\Application;

abstract class ApplicationTestCase extends \PHPUnit_Framework_TestCase
{
    const ENV = 'test';

    protected static $app = null;

    /**
     * Get the application object
     *
     * @return Application
     */
    public function getApplication()
    {
        // Reset application
        Application::setAppDir(__DIR__.'/../../../');
        Application::setBundles([]);

        if (static::$app === null) {
            static::$app = new Application(self::ENV, true);
        }

        return static::$app;
    }

} 