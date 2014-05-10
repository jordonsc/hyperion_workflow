<?php
namespace Hyperion\Workflow\Factory;

use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Driver\ApiDriver;

/**
 * Create a Hyperion DBAL Manager
 */
class DataManagerFactory
{

    public static function get($config)
    {
        $driver = new ApiDriver($config['hostname']);
        return new DataManager($driver);
    }

}
 