<?php
namespace Hyperion\Workflow\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static WorkflowResult COMPLETE()
 * @method static WorkflowResult FAIL()
 * @method static WorkflowResult TIMEOUT()
 * @method static WorkflowResult COMMAND()
 */
class WorkflowResult extends AbstractEnumeration
{
    const COMPLETE = 'COMPLETE';
    const FAIL     = 'FAIL';
    const TIMEOUT  = 'TIMEOUT';
    const COMMAND  = 'COMMAND';
} 