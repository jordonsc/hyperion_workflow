<?php
namespace Hyperion\Database\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class DoctrineFactory
{

    /**
     * Create a new entity manager
     *
     * @param array $db_conf
     * @return EntityManager
     */
    public function get(array $db_conf)
    {
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entities"), true);

        return EntityManager::create($db_conf, $config);
    }

} 