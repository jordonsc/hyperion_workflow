<?php
namespace Hyperion\Tests\Framework\Resources\Utility;

use Hyperion\Framework\Utility\ConfigTrait;

class ConfigurableClass
{
    use ConfigTrait;

    public function init($conf)
    {
        $this->config = $conf;
    }

    public function get($key, $default = null, $delimiter = '.')
    {
        return $this->getConfig($key, $default, $delimiter);
    }

    public function set($key, $value, $delimiter = '.')
    {
        $this->setConfig($key, $value, $delimiter);
    }

}