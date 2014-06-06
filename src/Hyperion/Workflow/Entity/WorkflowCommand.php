<?php
namespace Hyperion\Workflow\Entity;

class WorkflowCommand
{
    const DEFAULT_TIMEOUT = 300;

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
     * @var int
     */
    protected $timeout;

    function __construct($command, $params = [], $result_namespace = null, $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->command          = $command;
        $this->params           = $params;
        $this->result_namespace = $result_namespace;
        $this->timeout          = $timeout;
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
     * Return the command in JSON format
     *
     * @return string
     */
    public function serialise()
    {
        $out = [
            'c'  => $this->getCommand(),
            'p'  => $this->getParams(),
            'ns' => $this->getResultNamespace(),
            'to' => $this->getTimeout(),
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
        $obj = new self(isset($in['c']) ? $in['c'] : null,
            isset($in['p']) ? $in['p'] : null,
            isset($in['ns']) ? $in['ns'] : null,
            isset($in['to']) ? $in['to'] : self::DEFAULT_TIMEOUT);
        return $obj;
    }

}
