<?php


namespace Hyperion\Tests\Framework\Resources\Services;


class TestDependency
{

    protected $param;

    protected $service;

    function __construct($param, TestService $service)
    {
        $this->param = $param;
        $this->service = $service;
    }

    /**
     * Set param
     *
     * @param mixed $param
     * @return TestDependency
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

    /**
     * Set service
     *
     * @param TestService $service
     * @return TestDependency
     */
    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Get service
     *
     * @return TestService
     */
    public function getService()
    {
        return $this->service;
    }

    

} 