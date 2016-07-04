<?php namespace CoasterCms\Events\Cms;

use Symfony\Component\HttpFoundation\Response;

class LoadedPageResponse
{
    /**
     * @var Response
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
