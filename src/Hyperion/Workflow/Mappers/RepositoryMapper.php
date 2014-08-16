<?php
namespace Hyperion\Workflow\Mappers;

use Bravo3\Bakery\Entity\Repository as BakeRepository;
use Bravo3\Bakery\Enum\RepositoryType;
use Bravo3\NetworkProxy\Implementation\HttpProxy;
use Bravo3\NetworkProxy\Implementation\SocksProxy;
use Hyperion\Dbal\Entity\Proxy;
use Hyperion\Dbal\Entity\Repository as DbalRepository;
use Hyperion\Dbal\Enum\ProxyType;

class RepositoryMapper
{

    /**
     * Convert a DBAL Repository to a Bakery Repository
     *
     * @param DbalRepository $repo
     * @param Proxy          $proxy
     * @return BakeRepository
     */
    public static function DbalToBakery(DbalRepository $repo, Proxy $proxy = null)
    {
        $bake_repo = new BakeRepository();
        $bake_repo->setRepositoryType(RepositoryType::memberByKey($repo->getType()->key()));
        $bake_repo->setUri($repo->getUrl());
        $bake_repo->setUsername($repo->getUsername());
        $bake_repo->setPassword($repo->getPassword());
        $bake_repo->setPrivateKey($repo->getPrivateKey());
        $bake_repo->setCheckoutPath($repo->getCheckoutDirectory());
        $bake_repo->setTag($repo->getTag());
        $bake_repo->setHostFingerprint($repo->getHostFingerprint());

        if ($proxy) {
            switch ($proxy->getType()) {
                default:
                case ProxyType::HTTP():
                    $bake_proxy = new HttpProxy(
                        $proxy->getHostname(),
                        $proxy->getPort(),
                        $proxy->getUsername(),
                        $proxy->getPassword()
                    );
                    break;
                case ProxyType::SOCKS5():
                    $bake_proxy = new SocksProxy(
                        $proxy->getHostname(),
                        $proxy->getPort(),
                        $proxy->getUsername(),
                        $proxy->getPassword()
                    );
                    break;
            }

            $bake_repo->setProxy($bake_proxy);
        }

        return $bake_repo;
    }
} 