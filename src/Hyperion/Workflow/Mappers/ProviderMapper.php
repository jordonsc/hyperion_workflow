<?php
namespace Hyperion\Workflow\Mappers;

use Bravo3\CloudCtrl\Enum\Provider as CloudProvider;
use Hyperion\Dbal\Enum\Provider;

class ProviderMapper
{

    /**
     * Convert a Bravo3\CloudCtrl\Enum\Provider to a Hyperion\Dbal\Enum\Provider
     *
     * @param CloudProvider $provider
     * @return Provider
     */
    public static function CloudCtrlToDbal(CloudProvider $provider)
    {
        switch ($provider) {
            default:
                return null;
            case CloudProvider::AWS():
                return Provider::AWS();

            case CloudProvider::GOOGLE():
                return Provider::GOOGLE_CLOUD();

            case CloudProvider::AZURE():
                return Provider::WINDOWS_AZURE();
        }
    }

    /**
     * Convert a Hyperion\Dbal\Enum\Provider to a Bravo3\CloudCtrl\Enum\Provider
     *
     * @param Provider $provider
     * @return CloudProvider
     */
    public static function DbalToCloudCtrl(Provider $provider)
    {
        switch ($provider) {
            default:
                return null;
            case Provider::AWS():
                return CloudProvider::AWS();
            case Provider::GOOGLE_CLOUD():
                return CloudProvider::GOOGLE();
            case Provider::WINDOWS_AZURE():
                return CloudProvider::AZURE();
        }
    }

}