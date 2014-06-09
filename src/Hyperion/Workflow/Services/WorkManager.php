<?php
namespace Hyperion\Workflow\Services;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Hyperion\Workflow\Entity\WorkTask;
use Hyperion\Workflow\Exception\CommandFailedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;

class WorkManager implements LoggerAwareInterface
{
    const IDENTITY = 'Hyperion Workflow Worker';
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
     * @var CommandManager
     */
    protected $command_driver;

    public function __construct(array $swf_config, array $config, CommandManager $command_driver)
    {
        $this->swf_config     = $swf_config;
        $this->config         = $config;
        $this->aws            = Aws::factory($config);
        $this->swf            = $this->aws->get('swf');
        $this->command_driver = $command_driver;
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
                        'name' => $this->swf_config['tasklist'],
                    ),
                    'identity' => self::IDENTITY,
                ]
            )
        );

        return $task;
    }

    /**
     * Execute the WorkflowCommand of the WorkTask and respond accordingly
     *
     * @param WorkTask $task
     */
    public function processWorkTask(WorkTask $task)
    {
        try {
            $this->command_driver->execute($task->getWorkflowCommand());
            $this->respondSuccess($task);
        } catch (CommandFailedException $e) {
            $this->respondFailed($task, $e->getMessage());
        }
    }

    /**
     * Mark an action/workflow as complete
     *
     * @param WorkTask $task
     * @param string   $result
     */
    public function respondSuccess(WorkTask $task, $result = 'OK')
    {
        $this->log(LogLevel::DEBUG, "Activity ".$task->getExecutionId()." succeeded: ".$result);

        $this->swf->respondActivityTaskCompleted(
            [
                'taskToken' => $task->getToken(),
                'result'    => $result
            ]
        );
    }

    /**
     * Mark an action/workflow as failed
     *
     * @param WorkTask $task
     * @param string   $reason
     */
    public function respondFailed(WorkTask $task, $reason)
    {
        $this->log(LogLevel::ERROR, "Activity ".$task->getExecutionId()." failed: ".$reason);

        $this->swf->respondActivityTaskFailed(
            [
                'taskToken' => $task->getToken(),
                'reason'    => $reason
            ]
        );
    }

    protected function log($level, $message, $context = [])
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

}
