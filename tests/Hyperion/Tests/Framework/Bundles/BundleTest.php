<?php


namespace Hyperion\Tests\Framework\Bundles;


use Hyperion\Tests\Framework\Resources\Bundles\TestBundle;
use Hyperion\Framework\Engine\Application;
use Hyperion\Tests\Framework\ApplicationTestCase;

class BundleTest extends ApplicationTestCase
{
    /**
     * @small
     */
    public function testBundling()
    {
        Application::setAppDir(__DIR__.'/..');

        // Clear bundle stack, add a new one
        Application::setBundles([]);
        $this->assertCount(0, Application::getBundles());

        // This bundle will register some params, expect an exception if there is something wrong
        Application::addBundle(new TestBundle());
        $this->assertCount(1, Application::getBundles());

        // Test for any exceptions with this
        $app = new Application(self::ENV);
        $app->rebuildContainer();
    }

}
 