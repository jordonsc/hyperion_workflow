<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Stages of the bakery process
 */
class BakeStage extends AbstractEnumeration
{
    const SPAWNING = 0;
    const BAKING   = 10;
    const SHUTDOWN = 30;
    const SAVING   = 40;
    const CLEANUP  = 50;
}
 