<?php
namespace Hyperion\Workflow\Mappers;

use Bravo3\CloudCtrl\Enum\InstanceState as CloudInstanceState;
use Hyperion\Dbal\Enum\InstanceState;

class InstanceStateMapper
{

    /**
     * Convert a Bravo3\CloudCtrl\Enum\InstanceState to a Hyperion\Dbal\Enum\InstanceState
     *
     * @param CloudInstanceState $state
     * @return InstanceState
     */
    public static function CloudCtrlToDbal(CloudInstanceState $state)
    {
        switch ($state) {
            default:
            case CloudInstanceState::PENDING():
                return InstanceState::PENDING();
            case CloudInstanceState::STARTING():
                return InstanceState::STARTING();
            case CloudInstanceState::RUNNING():
                return InstanceState::RUNNING();
            case CloudInstanceState::STOPPING():
                return InstanceState::STOPPING();
            case CloudInstanceState::STOPPED():
                return InstanceState::STOPPED();
            case CloudInstanceState::TERMINATING():
                return InstanceState::TERMINATING();
            case CloudInstanceState::TERMINATED():
                return InstanceState::TERMINATED();
        }
    }

    /**
     * Convert a Hyperion\Dbal\Enum\InstanceState to a Bravo3\CloudCtrl\Enum\InstanceState
     *
     * @param InstanceState $state
     * @return CloudInstanceState
     */
    public static function DbalToCloudCtrl(InstanceState $state)
    {
        switch ($state) {
            default:
            case InstanceState::PENDING():
                return CloudInstanceState::PENDING();
            case InstanceState::STARTING():
                return CloudInstanceState::STARTING();
            case InstanceState::RUNNING():
                return CloudInstanceState::RUNNING();
            case InstanceState::STOPPING():
                return CloudInstanceState::STOPPING();
            case InstanceState::STOPPED():
                return CloudInstanceState::STOPPED();
            case InstanceState::TERMINATING():
                return CloudInstanceState::TERMINATING();
            case InstanceState::TERMINATED():
                return CloudInstanceState::TERMINATED();
        }
    }

} 