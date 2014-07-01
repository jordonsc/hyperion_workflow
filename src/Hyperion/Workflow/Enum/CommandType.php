<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * List of supported command drivers
 */
class CommandType extends AbstractEnumeration
{
    // Generic commands
    const CHECK_CONNECTIVITY = 'General\CheckConnectivityDriver';

    // Instance commands
    const LAUNCH_INSTANCE    = 'Instance\CreateInstanceDriver';
    const CHECK_INSTANCE     = 'Instance\CheckInstanceDriver';
    const BAKE_INSTANCE      = 'Instance\BakeDriver';
    const SHUTDOWN_INSTANCE  = 'Instance\ShutdownInstanceDriver';
    const TERMINATE_INSTANCE = 'Instance\TerminateInstanceDriver';
    const RESTART_INSTANCE   = 'Instance\RestartInstanceDriver';

    // Image commands
    const CREATE_IMAGE     = 'Image\CreateImageDriver';
    const CHECK_IMAGE      = 'Image\CheckImageDriver';
    const DEREGISTER_IMAGE = 'Image\DeregisterImageDriver';

    // IP commands

    // Storage commands

    // Load balancer commands

    // VPC commands


}
