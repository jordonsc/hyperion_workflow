<?php
namespace Hyperion\Workflow\Services;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;

class WorkflowManager
{
    const WORKFLOW_NAME    = 'std_action';
    const WORKFLOW_VERSION = '1.0.0';
    const TASKLIST         = 'action_worker';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Aws
     */
    protected $aws;

    /**
     * @var SwfClient
     */
    protected $swf;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->aws    = Aws::factory($config);
        $this->swf    = $this->aws->get('swf');
    }

    /**
     * Create a new SWF workflow execution
     *
     * @param string $workflow_id      Workflow ID
     * @param string $input            Workflow input
     * @param int    $workflow_timeout Workflow timeout in seconds
     * @param int    $task_timeout     Default task timeout in seconds
     */
    protected function createWorkflow($workflow_id, $input, $workflow_timeout = 3600, $task_timeout = 300)
    {
        $this->swf->startWorkflowExecution(
            [
                'domain'                       => $this->config['domain'],
                'workflowId'                   => $workflow_id,
                'workflowType'                 => [
                    'name'    => self::WORKFLOW_NAME,
                    'version' => self::WORKFLOW_VERSION,
                ],
                'taskList'                     => [
                    'name' => self::TASKLIST,
                ],
                'input'                        => $input,
                'executionStartToCloseTimeout' => (string)$workflow_timeout,
                'taskStartToCloseTimeout'      => (string)$task_timeout,
                'childPolicy'                  => 'TERMINATE',
            ]
        );
    }

}
