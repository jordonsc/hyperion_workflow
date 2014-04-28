<?php


namespace Hyperion\Tests\Framework\Resources\Services;


class TestService
{

    protected $param;

    function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Set param
     *
     * @param mixed $param
     * @return TestService
     */
    public function setParam($param)
    {
        $this->param = $param;
        return $this;
    }

    /**
     * Get param
     *
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }



} 