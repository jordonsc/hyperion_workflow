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
     * @var bool
     */
    protected $has_failures = false;

    /**
     * @var string[]
     */
    protected $errors = [];

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
     * Mark the execution as having failures
     *
     * @return $this
     */
    public function fail($reason)
    {
        $this->has_failures = true;
        $this->errors[] = $reason;
        return $this;
    }

    /**
     * Check if any activities has failed
     *
     * @return boolean
     */
    public function hasFailed()
    {
        return $this->has_failures;
    }

    /**
     * Get Errors
     *
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
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
                // Get the workflow input (action ID) - relying on SWF to be reliable in their response here
                if ($event['eventType'] == 'WorkflowExecutionStarted') {
                //if (isset($event['workflowExecutionStartedEventAttributes'])) {
                    $task->setActionId($event['workflowExecutionStartedEventAttributes']['input']);
                }

                // Check for activity failures
                if ($event['eventType'] == 'ActivityTaskFailed') {
                    $task->fail('Failed: '.$event['activityTaskFailedEventAttributes']['reason']);
                } elseif ($event['eventType'] == 'ActivityTaskCanceled') {
                    $task->fail('Canceled: '.$event['activityTaskCanceledEventAttributes']['details']);
                } elseif ($event['eventType'] == 'ActivityTaskTimedOut') {
                    $task->fail('Timeout');
                }
            }
        }

        return $task;
    }
} 