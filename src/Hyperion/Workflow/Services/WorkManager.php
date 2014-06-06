<?php
namespace Hyperion\Workflow\Services;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\StackManager;
use Hyperion\Workflow\Entity\WorkflowTask;
use Hyperion\Workflow\Entity\WorkTask;

class WorkManager
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
     * Get a work task
     *
     * @return WorkTask|null
     */
    public function getWorkTask()
    {
        $task = WorkTask::fromGuzzleModel(
            $this->swf->pollForActivityTask(
                [
                    'domain'   => $this->config['domain'],
                    'taskList' => array(
                        'name' => self::TASKLIST,
                    ),
                    'identity' => 'Hyperion Workflow Decider',
                ]
            )
        );

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
    protected function getActionForTask(WorkflowTask $task)
    {
        return $task->getActionId() ? $this->dm->retrieve(Entity::ACTION(), $task->getActionId()) : null;
    }


}
