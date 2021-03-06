<?php
namespace Hyperion\Workflow\Entity;

use Hyperion\Dbal\Entity\Action;

class WorkflowCommand
{
    const DEFAULT_TIMEOUT = 300;

    /**
     * @var int
     */
    protected $action;

    /**
     * @var int
     */
    protected $project;

    /**
     * @var int
     */
    protected $environment;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string
     */
    protected $result_namespace;

    /**
     * Start to close timeout in seconds
     *
     * This is for the workflow controller, the worker itself shouldn't need to worry about this
     *
     * @var int
     */
    protected $timeout;

    function __construct(
        Action $action,
        $command,
        $params = [],
        $result_namespace = null,
        $timeout = self::DEFAULT_TIMEOUT
    ) {
        $this->action           = $action->getId();
        $this->project          = $action->getProject();
        $this->environment      = $action->getEnvironment();
        $this->command          = $command;
        $this->params           = $params;
        $this->result_namespace = $result_namespace;
        $this->timeout          = $timeout;
    }

    /**
     * Set Environment
     *
     * @param int $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * Get Environment
     *
     * @return int
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Set Project
     *
     * @param int $project
     * @return $this
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * Get Project
     *
     * @return int
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set Command
     *
     * @param string $command
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    /**
     * Get Command
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set Params
     *
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set ResultNamespace
     *
     * @param string $result_namespace
     * @return $this
     */
    public function setResultNamespace($result_namespace)
    {
        $this->result_namespace = $result_namespace;
        return $this;
    }

    /**
     * Get ResultNamespace
     *
     * @return string
     */
    public function getResultNamespace()
    {
        return $this->result_namespace;
    }

    /**
     * Set Timeout
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get Timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set Action
     *
     * @param int $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get Action
     *
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Return the command in JSON format
     *
     * @return string
     */
    public function serialise()
    {
        $out = [
            'act'  => $this->getAction(),
            'prj'  => $this->getProject(),
            'env'  => $this->getEnvironment(),
            'cmd'  => $this->getCommand(),
            'para' => $this->getParams(),
            'ns'   => $this->getResultNamespace(),
            'to'   => $this->getTimeout(),
        ];

        return json_encode($out);
    }

    /**
     * Create a new WorkflowCommand from a JSON string
     *
     * @param $str
     * @return WorkflowCommand
     */
    public static function deserialise($str)
    {
        $in  = json_decode($str, true);

        $action = new Action();
        $action->setId(isset($in['act']) ? $in['act'] : null);
        $action->setProject(isset($in['prj']) ? $in['prj'] : null);
        $action->setEnvironment(isset($in['env']) ? $in['env'] : null);

        $obj = new static(
            $action,
            isset($in['cmd']) ? $in['cmd'] : null,
            isset($in['para']) ? $in['para'] : null,
            isset($in['ns']) ? $in['ns'] : null,
            isset($in['to']) ? $in['to'] : self::DEFAULT_TIMEOUT
        );
        return $obj;
    }

}
