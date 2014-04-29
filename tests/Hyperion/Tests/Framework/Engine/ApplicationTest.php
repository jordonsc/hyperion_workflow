<?php

namespace Hyperion\Tests\Framework\Engine;

use Hyperion\Framework\Engine\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    const ENV = 'test';

    /**
     * @var Application
     */
    protected $app;

    const DEFAULT_NAME = 'Hyperion Framework';

    public function setUp()
    {
        Application::setAppDir(__DIR__.'/..');
        $this->app = new Application(self::ENV);
        $this->app->rebuildContainer();

    }

    /**
     * @small
     */
    public function testAppProperties()
    {
        $this->app->setEnvironment('prod');
        $this->app->setDebug(true);   // set debug to true in prod?!

        $this->assertEquals('prod', $this->app->getEnvironment());
        $this->assertTrue($this->app->getDebug());

        $this->assertEquals(self::DEFAULT_NAME, $this->app->getName());
    }

    /**
     * @small
     */
    public function testContainer()
    {
        $container = $this->app->getContainer();
        $a = $container->getParameter('test')['integer'];
        $this->assertEquals(10, $a);

        $b = $this->app->getProperty('test')['integer'];
        $this->assertEquals(10, $b);
    }

    /**
     * @small
     */
    public function testDefaultProperty()
    {
        $property = $this->app->getProperty('doesntexist', 7);
        $this->assertEquals(7, $property);
    }



}
 