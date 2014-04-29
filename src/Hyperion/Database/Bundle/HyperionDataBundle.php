<?php

namespace Hyperion\Database\Bundle;

use Hyperion\Framework\Engine\Application;
use Hyperion\Framework\Engine\BundleInterface;

class HyperionDataBundle implements BundleInterface
{
    public function init(Application &$application)
    {
        $application->importConfig(__DIR__.'/../Resources/config.yml');
    }
} 