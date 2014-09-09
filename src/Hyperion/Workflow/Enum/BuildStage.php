<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Stage of the build (CI) process
 */
final class BuildStage extends AbstractEnumeration
{
    const SPAWNING    = 0;
    const BUILDING    = 10;
    const CONFIGURING = 20;
}
