<?php

namespace Hyperion\Framework\Engine;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application extends ConsoleApplication implements ContainerAwareInterface
{

    const APPLICATION_NAME    = 'Hyperion Framework';
    const APPLICATION_VERSION = '1.0.0';

    /**
     * @var string Application environment [dev, prod]
     */
    protected $env;

    /**
     * @var boolean Debug mode
     */
    protected $debug;

    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @var string
     */
    protected static $app_dir = null;

    /**
     * @var string[]
     */
    protected static $bundles = [];


    public function __construct($env = 'dev', $debug = null)
    {
        if ($debug === null) {
            $debug = $env == 'dev';
        }
        $this->env   = $env;
        $this->debug = $debug;

        parent::__construct(static::APPLICATION_NAME, static::APPLICATION_VERSION);

        $this->rebuildContainer();
    }

    /**
     * Load parameters and build services
     */
    public function rebuildContainer()
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(static::getAppDir()));
        $loader->load('config_'.$this->env.'.yml');

        $this->setContainer($container);
        $this->loadBundles();
    }

    /**
     * Import additional config
     *
     * @param $fn
     */
    public function importConfig($fn)
    {
        $loader = new YamlFileLoader($this->container, new FileLocator(static::getAppDir()));
        $loader->import($fn);
    }

    /**
     * Load all bundles, adding their commands and importing their config
     */
    protected function loadBundles()
    {
        /**
         * @var $bundle BundleInterface
         */
        foreach (static::$bundles as $bundle) {
            $bundle->init($this);
        }
    }

    /**
     * Set debug mode
     *
     * @param boolean $debug
     * @return Application
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Get debug mode
     *
     * @return boolean
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set application environment
     *
     * @param string $env
     * @return Application
     */
    public function setEnvironment($env)
    {
        $this->env = $env;
        return $this;
    }

    /**
     * Get application environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Set container
     *
     * @param ContainerInterface $container
     * @return Application
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get a service from the container
     *
     * @param $name
     * @return mixed
     */
    public function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * Get a property from the parameter bag
     *
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        try {
            return $this->container->getParameter($name);
        } catch (InvalidArgumentException $e) {
            return $default;
        }
    }

    /**
     * Set the application root directory
     *
     * @param string $app_dir
     */
    public static function setAppDir($app_dir)
    {
        self::$app_dir = $app_dir;
    }

    /**
     * Get the application root directory
     *
     * @return string
     */
    public static function getAppDir()
    {
        return self::$app_dir;
    }

    /**
     * Set bundles
     *
     * @param \string[] $bundles
     */
    public static function setBundles($bundles)
    {
        self::$bundles = $bundles;
    }

    /**
     * Get bundles
     *
     * @return \string[]
     */
    public static function getBundles()
    {
        return self::$bundles;
    }


    /**
     * Add a bundle
     *
     * @param string $bundle Relative path to the root directory of the bundle
     */
    public static function addBundle($bundle)
    {
        self::$bundles[] = $bundle;
    }

} 