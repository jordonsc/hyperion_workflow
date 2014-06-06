<?php
namespace Hyperion\Workflow\Factory;

use Hyperion\Dbal\Driver\ApiStackDriver;
use Hyperion\Dbal\StackManager;

/**
 * Create a Hyperion Stack Manager
 */
class StackManagerFactory
{

    public static function get($config)
    {
        $driver = new ApiStackDriver($config['hostname']);
        return new StackManager($driver);
    }

}
 