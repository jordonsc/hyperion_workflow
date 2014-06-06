<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

class CommandType extends AbstractEnumeration
{
    // Instance commands
    const LAUNCH_INSTANCE     = 'LAUNCH_INSTANCE';
    const WAIT_CHECK_INSTANCE = 'WAIT_CHECK_INSTANCE';
    const CHECK_INSTANCE      = 'CHECK_INSTANCE';
    const SHUTDOWN_INSTANCE   = 'SHUTDOWN_INSTANCE';
    const TERMINATE_INSTANCE  = 'TERMINATE_INSTANCE';

    // IP commands

    // Storage commands

    // Load balancer commands

    // VPC commands


}
