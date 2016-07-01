<?php namespace CoasterCms\Events\Cms;

class InitializePageBuilder
{
    /**
     * @var string
     */
    public $pageLoader;

    /**
     * @var array
     */
    public $pageBuilder;

    /**
     * InitializePageBuilder constructor.
     * @param $pageLoader
     * @param $pageBuilder
     */
    public function __construct(&$pageLoader, &$pageBuilder)
    {
        $this->pageLoader = &$pageLoader;
        $this->pageBuilder = &$pageBuilder;
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
