<?php

namespace Hyperion\Framework\Command;

use Hyperion\Framework\Engine\Application;
use Symfony\Component\Console\Command\Command;

abstract class ApplicationCommand extends Command
{

    /**
     * Get a service from the applications container
     *
     * @param string $name
     * @return mixed
     */
    protected function getService($name)
    {
        /**
         * @var $app Application
         */
        $app = $this->getApplication();
        if (!($app instanceof Application)) throw new \Exception("This command was not registered to a Framework Application");
        return $app->getService($name);
    }

    /**
     * Get a property from the applications property bag
     *
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    protected function getProperty($name, $default = null)
    {
        /**
         * @var $app Application
         */
        $app = $this->getApplication();
        if (!($app instanceof Application)) throw new \Exception("This command was not registered to a Framework Application");
        return $app->getProperty($name, $default);
    }

    /**
     * Gets the application instance for this command.
     *
     * @return Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

}