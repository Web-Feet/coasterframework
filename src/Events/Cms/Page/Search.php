<?php namespace CoasterCms\Events\Cms\Page;

use CoasterCms\Helpers\Cms\Page\Search\Cms;

class Search
{
    /**
     * @var Cms[]
     */
    public $searchObjects;

    /**
     * @var bool
     */
    public $onlyLive;

    /**
     * LoadAuth constructor.
     * @param Cms[] $searchObjects
     * @param bool $onlyLive
     */
    public function __construct(&$searchObjects, &$onlyLive)
    {
        $this->_searchObjects = &$searchObjects;
        $this->_onlyLive = &$onlyLive;
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
