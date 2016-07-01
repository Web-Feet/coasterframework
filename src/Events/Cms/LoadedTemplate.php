<?php namespace CoasterCms\Events\Cms;

class LoadedTemplate
{
    /**
     * @var string
     */
    public $renderedTemplate;

    /**
     * LoadedTemplate constructor.
     * @param $renderedTemplate
     */
    public function __construct(&$renderedTemplate)
    {
        $this->renderedTemplate = &$renderedTemplate;
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
