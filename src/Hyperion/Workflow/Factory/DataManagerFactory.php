<?php
namespace Hyperion\Workflow\Factory;

use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Driver\ApiDataDriver;

/**
 * Create a Hyperion Data Manager
 */
class DataManagerFactory
{

    public static function get($config)
    {
        $driver = new ApiDataDriver($config['hostname']);
        return new DataManager($driver);
    }

}
 