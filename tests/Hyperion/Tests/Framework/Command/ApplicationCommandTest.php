<?php


namespace Hyperion\Framework\Tests\Command;


use Hyperion\Framework\Engine\Application;
use Hyperion\Tests\Framework\ApplicationTestCase;
use Hyperion\Tests\Framework\Resources\Command\TestCommand;
use Hyperion\Tests\Framework\Resources\Services\TestService;

class ApplicationCommandTest extends ApplicationTestCase
{

    /**
     * @small
     * @expectedException \Exception
     */
    public function testInvalidAbstraction()
    {
        $command = new TestCommand();
        $this->assertEquals(10, $command->testPropertyAbstraction());   // not available until registered to the app properly
    }

    /**
     * @small
     */
    public function testValidAbstraction()
    {
        Application::setAppDir(__DIR__.'/..');
        $app = new Application(self::ENV);
        $app->rebuildContainer();

        $command = new TestCommand();
        $app->add($command);

        $this->assertEquals(10, $command->testPropertyAbstraction());

        $service = $command->testServiceAbstraction();
        $this->assertTrue($service instanceof TestService);
    }

}
 