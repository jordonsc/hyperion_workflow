<?php
namespace Hyperion\Workflow\Mappers;

use Bravo3\Bakery\Enum\PackagerType;
use Hyperion\Dbal\Enum\Packager;

class PackagerTypeMapper
{

    /**
     * Convert a bakery PackagerType to a DBAL Packager
     *
     * @param PackagerType $packager
     * @return Packager
     */
    public static function bakeryToDbal(PackagerType $packager)
    {
        return Packager::memberByKey($packager->key());
    }

    /**
     * Convert a DBAL Packager to a Bakery PackagerType
     *
     * @param Packager $packager
     * @return PackagerType
     */
    public static function dbalToBakery(Packager $packager)
    {
        return PackagerType::memberByKey($packager->key());
    }

}
