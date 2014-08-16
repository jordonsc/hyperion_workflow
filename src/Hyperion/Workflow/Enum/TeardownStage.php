<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Stages of the tear-down process
 */
class TeardownStage extends AbstractEnumeration
{
    const TERMINATING         = 0;
    const WAIT_FOR_COMPLETION = 10;
}
