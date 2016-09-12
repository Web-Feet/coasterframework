<?php namespace CoasterCms\Events\Cms\GeneratePage;

class InitializePageBuilder
{
    /**
     * @var array
     */
    public $pageLoader;

    /**
     * @var array
     */
    public $pageBuilder;

    /**
     * InitializePageBuilder constructor.
     * @param string $pageLoader
     * @param array $pageBuilder
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
