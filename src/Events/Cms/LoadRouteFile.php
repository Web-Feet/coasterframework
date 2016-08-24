<?php namespace CoasterCms\Events\Cms;

class LoadRouteFile
{
    /**
     * @var string
     */
    public $routeFile;

    /**
     * LoadRouteFile constructor.
     * @param string $routeFile
     */
    public function __construct(&$routeFile)
    {
        $this->routeFile = &$routeFile;
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
