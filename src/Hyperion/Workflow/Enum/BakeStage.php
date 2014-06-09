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
    const SAVING   = 20;
    const CLEANUP  = 30;
}
 