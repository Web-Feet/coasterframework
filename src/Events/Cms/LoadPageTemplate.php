<?php namespace CoasterCms\Events\Cms;

class LoadPageTemplate
{
    /**
     * @var string
     */
    public $template;

    /**
     * LoadPageTemplate constructor.
     * @param $template
     */
    public function __construct(&$template)
    {
        $this->template = &$template;
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
