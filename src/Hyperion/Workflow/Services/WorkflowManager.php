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

    public function getDecisionTask()
    {
        $task = $this->swf->pollForDecisionTask([
                'domain' => $this->config['domain'],
                'taskList' => array(
                    'name' => self::TASKLIST,
                ),
                'identity' => 'Hyperion Workflow Decider',
            ]);

        return $task;
    }

}
