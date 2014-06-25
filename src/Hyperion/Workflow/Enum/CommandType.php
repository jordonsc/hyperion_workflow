<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

class CommandType extends AbstractEnumeration
{
    // Generic commands
    const CHECK_CONNECTIVITY = 'CHECK_CONNECTIVITY';

    // Instance commands
    const LAUNCH_INSTANCE     = 'LAUNCH_INSTANCE';
    const CHECK_INSTANCE      = 'CHECK_INSTANCE';
    const BAKE_INSTANCE       = 'BAKE_INSTANCE';
    const SHUTDOWN_INSTANCE   = 'SHUTDOWN_INSTANCE';
    const TERMINATE_INSTANCE  = 'TERMINATE_INSTANCE';

    // IP commands

    // Storage commands

    // Load balancer commands

    // VPC commands


}
