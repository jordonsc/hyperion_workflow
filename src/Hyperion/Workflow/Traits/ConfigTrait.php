<?php
namespace Hyperion\Workflow\Traits;

trait ConfigTrait
{
    protected $config = [];

    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed  $default
     * @param string $delim
     * @return mixed
     */
    protected function getConfig($key, $default = null, $delimiter = '.')
    {
        $path = explode($delimiter, $key);

        $value = $this->config;
        foreach ($path as $index) {
            if (isset($value[$index])) {
                $value = $value[$index];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a configuration value using a path key
     *
     * @param $key
     * @param $value
     */
    protected function setConfig($key, $value, $delimiter = '.')
    {
        $path = explode($delimiter, $key);
        $final_key = array_pop($path);

        $arr =& $this->config;
        foreach ($path as $index) {
            if (!isset($arr[$index])) {
                $arr[$index] = [];
            } elseif (!is_array($arr[$index])) {
                $arr[$index] = [];
            }
        }

        $arr[$final_key] = $value;
    }

} 