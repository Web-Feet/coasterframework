<?php namespace CoasterCms\Events\Cms;

class SetViewPaths
{
    /**
     * @var array
     */
    public $adminViews;

    /**
     * @var array
     */
    public $frontendViews;

    /**
     * SetViewPaths constructor.
     * @param array $adminViews
     * @param array $frontendViews
     */
    public function __construct(&$adminViews, &$frontendViews)
    {
        $this->adminViews = &$adminViews;
        $this->frontendViews = &$frontendViews;
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
