<?php
namespace Hyperion\Workflow\Entity;

use Guzzle\Service\Resource\Model;
use Hyperion\Dbal\Entity\Action;

abstract class WorkflowTask
{

    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $startedEventId;

    /**
     * @var string
     */
    protected $workflowName;

    /**
     * @var string
     */
    protected $workflowVersion;

    /**
     * @var string
     */
    protected $executionId;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var int
     */
    protected $action_id;

    /**
     * @var Action
     */
    protected $action;

    /**
     * Set Token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get Token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

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
     * Set ExecutionId
     *
     * @param string $executionId
     * @return $this
     */
    public function setExecutionId($executionId)
    {
        $this->executionId = $executionId;
        return $this;
    }

    /**
     * Get ExecutionId
     *
     * @return string
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * Set RunId
     *
     * @param string $runId
     * @return $this
     */
    public function setRunId($runId)
    {
        $this->runId = $runId;
        return $this;
    }

    /**
     * Get RunId
     *
     * @return string
     */
    public function getRunId()
    {
        return $this->runId;
    }

    /**
     * Set StartedEventId
     *
     * @param int $startedEventId
     * @return $this
     */
    public function setStartedEventId($startedEventId)
    {
        $this->startedEventId = $startedEventId;
        return $this;
    }

    /**
     * Get StartedEventId
     *
     * @return int
     */
    public function getStartedEventId()
    {
        return $this->startedEventId;
    }

    /**
     * Set WorkflowName
     *
     * @param string $workflowName
     * @return $this
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
        return $this;
    }

    /**
     * Get WorkflowName
     *
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * Set WorkflowVersion
     *
     * @param string $workflowVersion
     * @return $this
     */
    public function setWorkflowVersion($workflowVersion)
    {
        $this->workflowVersion = $workflowVersion;
        return $this;
    }

    /**
     * Get WorkflowVersion
     *
     * @return string
     */
    public function getWorkflowVersion()
    {
        return $this->workflowVersion;
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
     * Create a new WorkflowTask from a Guzzle model
     *
     * @param Model $model
     * @return WorkflowTask|null
     */
    public static function fromGuzzleModel(Model $model) {
        /** @var WorkflowTask $task */
        $task = new static();
        $task->setStartedEventId($model->get('startedEventId'));

        if ($task->getStartedEventId() == 0) {
            return null;
        }

        $task->setToken($model->get('taskToken'));
        $task->setWorkflowName($model->get('workflowType')['name']);
        $task->setWorkflowVersion($model->get('workflowType')['version']);
        $task->setExecutionId($model->get('workflowExecution')['workflowId']);
        $task->setRunId($model->get('workflowExecution')['runId']);

        $events = $model->get('events');

        foreach ($events as $event) {
            if (isset($event['workflowExecutionStartedEventAttributes'])) {
                $task->setActionId($event['workflowExecutionStartedEventAttributes']['input']);
                break;
            }
        }

        return $task;
    }

} 