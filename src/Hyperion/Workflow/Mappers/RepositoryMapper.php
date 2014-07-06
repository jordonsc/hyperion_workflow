<?php
namespace Hyperion\Workflow\Mappers;

use Bravo3\Bakery\Entity\Repository as BakeRepository;
use Bravo3\Bakery\Enum\RepositoryType;
use Hyperion\Dbal\Entity\Repository as DbalRepository;

class RepositoryMapper
{

    /**
     * Convert a DBAL Repository to a Bakery Repository
     *
     * @param DbalRepository $repo
     * @return BakeRepository
     */
    public static function DbalToBakery(DbalRepository $repo) {
        $bake_repo = new BakeRepository();
        $bake_repo->setRepositoryType(RepositoryType::memberByKey($repo->getType()->key()));
        $bake_repo->setUri($repo->getUrl());
        $bake_repo->setUsername($repo->getUsername());
        $bake_repo->setPassword($repo->getPassword());
        $bake_repo->setPrivateKey($repo->getPrivateKey());
        $bake_repo->setCheckoutPath($repo->getCheckoutDirectory());
        $bake_repo->setTag($repo->getTag());
        $bake_repo->setHostFingerprint($repo->getHostFingerprint());
        return$bake_repo;
    }
} 