<?php

namespace Hyperion\Tests\Framework\Resources\Bundles;

use Hyperion\Framework\Engine\Application;
use Hyperion\Framework\Engine\BundleInterface;
use Hyperion\Tests\Framework\Resources\Command\TestCommand;

class TestBundle implements BundleInterface
{
    public function init(Application &$application)
    {
        $application->importConfig(__DIR__.'/params.yml');
        $application->add(new TestCommand());
    }
}
