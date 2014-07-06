<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * The action phases are a string (a hint) - not an enumeration. This class contains frequently used phases.
 */
final class ActionPhase extends AbstractEnumeration
{
    const PENDING       = 'PENDING';
    const SLEEPING      = 'SLEEPING';
    const CONNECTING    = 'CONNECTING';
    const SAVING        = 'SAVING';
    const CLEANUP       = 'CLEANUP';
    const SPAWNING      = 'SPAWNING';
    const SHUTTING_DOWN = 'SHUTTING_DOWN';
    const TERMINATING   = 'TERMINATING';
    const COMPLETE      = 'COMPLETE';
}