<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static ChangeType UPDATE()
 * @method static ChangeType DELETE()
 */
final class ChangeType extends AbstractEnumeration
{
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';
}
