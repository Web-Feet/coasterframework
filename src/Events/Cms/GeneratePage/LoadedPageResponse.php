<?php namespace CoasterCms\Events\Cms\GeneratePage;

use Symfony\Component\HttpFoundation\Response;

class LoadedPageResponse
{
    /**
     * @var Response
     */
    public $response;

    /**
     * LoadedPageResponse constructor.
     * @param Response $response
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
