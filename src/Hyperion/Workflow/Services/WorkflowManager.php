<?php
namespace Hyperion\Workflow\Services;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\StackManager;
use Hyperion\Workflow\Entity\WorkflowTask;

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

    /**
     * @var DataManager
     */
    protected $dm;

    /**
     * @var StackManager
     */
    protected $sm;


    public function __construct(array $config, DataManager $dm, StackManager $sm)
    {
        $this->config = $config;
        $this->aws    = Aws::factory($config);
        $this->swf    = $this->aws->get('swf');
        $this->dm     = $dm;
        $this->sm     = $sm;
    }

    /**
     * Get a decision task
     *
     * @return WorkflowTask|null
     */
    public function getDecisionTask()
    {
        $task = WorkflowTask::fromGuzzleModel($this->swf->pollForDecisionTask(
            [
                'domain'   => $this->config['domain'],
                'taskList' => array(
                    'name' => self::TASKLIST,
                ),
                'identity' => 'Hyperion Workflow Decider',
            ]
        ));

        if ($task) {
            $task->setAction($this->getActionForTask($task));
        }


        return $task;
    }

    /**
     * Get the corresponding DBAL action
     *
     * @param WorkflowTask $task
     * @return Action
     */
    protected function getActionForTask(WorkflowTask $task) {

    }


    public function completeAction(WorkflowTask $task)
    {
        // TODO: Close the workflow via SWF
        // TODO: Update action record via DBAL
    }

    public function failAction(WorkflowTask $task, $reason)
    {
        // TODO: Close the workflow via SWF
        // TODO: Update action record via DBAL
    }

    public function timeoutAction(WorkflowTask $task)
    {
        // TODO: Close the workflow via SWF
        // TODO: Update action record via DBAL
    }


}
