<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Stage of the build (CI) process
 */
class BuildStage extends AbstractEnumeration
{
    const SPAWNING = 0;
    const BUILDING = 10;
}
