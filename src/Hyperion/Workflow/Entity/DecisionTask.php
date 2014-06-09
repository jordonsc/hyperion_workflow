<?php
namespace Hyperion\Workflow\Entity;

use Guzzle\Service\Resource\Model;
use Hyperion\Dbal\Entity\Action;

class DecisionTask extends WorkflowTask
{

    /**
     * @var int
     */
    protected $action_id;

    /**
     * @var Action
     */
    protected $action;

    /**
     * Set ActionId
     *
     * @param int $action_id
     * @return $this
     */
    public function setActionId($action_id)
    {
        $this->action_id = $action_id;
        return $this;
    }

    /**
     * Get ActionId
     *
     * @return int
     */
    public function getActionId()
    {
        return $this->action_id;
    }

    /**
     * Set Action
     *
     * @param Action $action
     * @return $this
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get Action
     *
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Create a new DecisionTask from a Guzzle model
     *
     * @param Model $model
     * @return DecisionTask|null
     */
    public static function fromGuzzleModel(Model $model)
    {
        /** @var DecisionTask $task */
        $task = parent::fromGuzzleModel($model);

        if ($task) {
            $events = $model->get('events');

            foreach ($events as $event) {
                // Get the workflow input (action ID)
                if (isset($event['workflowExecutionStartedEventAttributes'])) {
                    $task->setActionId($event['workflowExecutionStartedEventAttributes']['input']);
                }

                // Check for activity failures
                // ..
            }
        }

        return $task;
    }
} 