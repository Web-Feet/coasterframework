<?php namespace CoasterCms\Events\Admin;

class AuthRoute
{
    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;
    
    /**
     * @var array
     */
    public $parameters;
    
    /**
     * @var array
     */
    public $returnOptions;

    /**
     * @var bool
     */
    public $ignore;

    /**
     * LoadResponse constructor.
     * @param string $controller
     * @param array $action
     * @param array $parameters
     * @param string $returnOptions
     */
    public function __construct($controller, $action, $parameters, &$returnOptions)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->parameters = $parameters;
        $this->returnOptions = &$returnOptions;
        $this->ignore = false;
    }
    
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

}
