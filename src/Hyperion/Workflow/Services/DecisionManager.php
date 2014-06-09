<?php
namespace Hyperion\Workflow\Services;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Bravo3\Cache\PoolInterface;
use Hyperion\Dbal\DataManager;
use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\Enum\ActionState;
use Hyperion\Dbal\Enum\ActionType;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Workflow\Entity\DecisionTask;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Entity\WorkflowTask;
use Hyperion\Workflow\Enum\WorkflowResult;
use Hyperion\Workflow\Exception\ParameterException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;

class DecisionManager implements LoggerAwareInterface
{
    const IDENTITY = 'Hyperion Workflow Decider';
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $swf_config;
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
     * @var PoolInterface
     */
    protected $cache_pool;

    public function __construct(array $swf_config, array $config, DataManager $dm, PoolInterface $cache_pool)
    {
        $this->swf_config = $swf_config;
        $this->config     = $config;
        $this->aws        = Aws::factory($config);
        $this->swf        = $this->aws->get('swf');
        $this->dm         = $dm;
        $this->cache_pool = $cache_pool;
    }

    /**
     * Get a decision task
     *
     * @return DecisionTask|null
     */
    public function getDecisionTask()
    {
        $task = DecisionTask::fromGuzzleModel(
            $this->swf->pollForDecisionTask(
                [
                    'domain'   => $this->config['domain'],
                    'taskList' => array(
                        'name' => $this->swf_config['tasklist'],
                    ),
                    'identity' => self::IDENTITY,
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
     * @param DecisionTask $task
     * @return Action
     */
    protected function getActionForTask(DecisionTask $task)
    {
        return $task->getActionId() ? $this->dm->retrieve(Entity::ACTION(), $task->getActionId()) : null;
    }

    /**
     * Generate decisions and process them
     *
     * @param WorkflowTask $task
     */
    public function processDecisionTask(DecisionTask $task)
    {
        $action = $task->getAction();

        if (!$action) {
            throw new ParameterException("Task does not have an associated action! Cannot process.");
        }

        // TODO: look for failed activities!

        switch ($action->getActionType()) {
            case ActionType::BAKE():
                $decider = new BakeDecider($action, $this->cache_pool);
                break;
            default:
                $this->respondFailed($task, "Unknown action type (".$action->getActionType()->value().")");
                return;
        }

        switch ($decider->getResult()) {
            // Schedule new worker commands
            case WorkflowResult::COMMAND():
                $commands = $decider->getCommands();
                $this->respondCommands($task, $commands);
                break;

            // All done, close the workflow
            case WorkflowResult::COMPLETE():
                $this->respondComplete($task);
                break;

            // Failure in action
            default:
            case WorkflowResult::FAIL():
                $this->respondFailed($task, $decider->getReason());
                break;

            // Timeout (a decider should never really throw this one)
            case WorkflowResult::TIMEOUT():
                $this->respondFailed($task, 'Timeout');
                break;
        }
    }

    /**
     * Respond to a decision with new commands (activity tasks)
     *
     * @param DecisionTask      $task
     * @param WorkflowCommand[] $commands
     */
    public function respondCommands(DecisionTask $task, $commands)
    {
        $decisions = [];

        /** @var WorkflowCommand $command */
        foreach ($commands as $index => $command) {
            $this->log(LogLevel::DEBUG, "Scheduling ".$command->getCommand()." for task ".$task->getExecutionId());

            $activity_id = 'r'.$task->getRunId().'.a'.$task->getActionId().'.c'.$command->getCommand().'.t'.
                           time().'.i'.$index.'-'.rand(10000, 99999);

            $decisions[] = [
                'decisionType'                           => 'ScheduleActivityTask',
                'scheduleActivityTaskDecisionAttributes' => [
                    'activityType'           => [
                        'name'    => $this->swf_config['activity_name'],
                        'version' => $this->swf_config['activity_version'],
                    ],
                    'activityId'             => $activity_id,
                    'input'                  => $command->serialise(),
                    'taskList'               => [
                        'name' => $this->swf_config['tasklist'],
                    ],
                    'scheduleToCloseTimeout' => (string)($command->getTimeout() + 300),
                    'heartbeatTimeout'       => (string)($command->getTimeout() + 300),
                    'startToCloseTimeout'    => (string)$command->getTimeout(),
                ]
            ];
        }

        $this->swf->respondDecisionTaskCompleted(
            [
                'taskToken' => $task->getToken(),
                'decisions' => $decisions
            ]
        );
    }

    /**
     * Mark an action/workflow as complete
     *
     * @param DecisionTask $task
     * @param string       $result
     */
    public function respondComplete(DecisionTask $task, $result = 'OK')
    {
        $this->log(LogLevel::DEBUG, "Completing task ".$task->getExecutionId());

        // Close SWF task
        $this->swf->respondDecisionTaskCompleted(
            [
                'taskToken' => $task->getToken(),
                'decisions' => [
                    [
                        'decisionType' => 'CompleteWorkflowExecution',
                        'result'       => $result
                    ]
                ]
            ]
        );

        // Update action record via DBAL
        $action = $task->getAction();
        $action->setState(ActionState::COMPLETED());
        $this->dm->update($action);
    }

    /**
     * Mark an action/workflow as failed
     *
     * @param DecisionTask $task
     * @param string       $reason
     */
    public function respondFailed(DecisionTask $task, $reason)
    {
        $this->log(LogLevel::DEBUG, "Failing task ".$task->getExecutionId().": ".$reason);

        // Close SWF task
        $this->swf->respondDecisionTaskCompleted(
            [
                'taskToken' => $task->getToken(),
                'decisions' => [
                    [
                        'decisionType' => 'FailWorkflowExecution',
                        'reason'       => $reason
                    ]
                ]
            ]
        );

        // Update action record via DBAL
        $action = $task->getAction();
        $action->setState(ActionState::FAILED());
        $this->dm->update($action);
    }

    protected function log($level, $message, $context = [])
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

}
