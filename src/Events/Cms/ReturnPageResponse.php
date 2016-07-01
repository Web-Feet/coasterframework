<?php namespace CoasterCms\Events\Cms;

class ReturnPageResponse
{
    /**
     * @var string
     */
    public $response;

    /**
     * ReturnPageResponse constructor.
     * @param $response
     */
    public function __construct(&$response)
    {
        $this->response = &$response;
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
