<?php
namespace Hyperion\Workflow\Entity;

use Guzzle\Service\Resource\Model;

class WorkTask extends WorkflowTask
{
    /**
     * @var string
     */
    protected $activity_id;

    /**
     * @var string
     */
    protected $input;

    /**
     * Set Activity Id
     *
     * @param string $activity_id
     * @return $this
     */
    public function setActivityId($activity_id)
    {
        $this->activity_id = $activity_id;
        return $this;
    }

    /**
     * Get Activity Id
     *
     * @return string
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set the serialised version of the WorkflowCommand
     *
     * @param string $input
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Get a serialised version of the WorkflowCommand
     *
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get the workflow command issued to this activity
     *
     * @return WorkflowCommand
     */
    public function getWorkflowCommand()
    {
        return WorkflowCommand::deserialise($this->input);
    }

    /**
     * Create a new WorkTask from a Guzzle model
     *
     * @param Model $model
     * @return WorkTask|null
     */
    public static function fromGuzzleModel(Model $model)
    {
        /** @var WorkTask $task */
        $task = parent::fromGuzzleModel($model);

        if ($task) {
            $task->setActivityId($model->get('activityId'));
            $task->setInput($model->get('input'));
        }

        return $task;
    }

} 