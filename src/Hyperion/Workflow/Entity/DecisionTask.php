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
        $this->errors[]     = $reason;
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
            $task->addHistory($model);
        }

        return $task;
    }

    /**
     * Check events for additional history
     *
     * @param Model $model
     */
    public function addHistory(Model $model)
    {
        $events = $model->get('events');
        foreach ($events as $event) {
            $this->processEvent($event);
        }
    }

    /**
     * Process an SWF event
     *
     * @param array $event
     */
    protected function processEvent(array $event)
    {
        // Get the workflow input (action ID)
        if ($event['eventType'] == 'WorkflowExecutionStarted') {
            $this->setActionId($event['workflowExecutionStartedEventAttributes']['input']);
        }

        // Check for activity failures
        if ($event['eventType'] == 'ActivityTaskFailed') {
            $this->fail($event['activityTaskFailedEventAttributes']['reason']);
        } elseif ($event['eventType'] == 'ActivityTaskCanceled') {
            $this->fail('Canceled: '.$event['activityTaskCanceledEventAttributes']['details']);
        } elseif ($event['eventType'] == 'ActivityTaskTimedOut') {
            $this->fail('Timeout');
        }
    }

} 