<?php namespace CoasterCms\Events\Cms;

class LoadConfig
{
    /**
     * @var string
     */
    public $configFile;

    /**
     * @var bool
     */
    public $useDatabaseSettings;

    /**
     * LoadConfig constructor.
     * @param string $configFile
     * @param bool $useDatabaseSettings
     */
    public function __construct(&$configFile, &$useDatabaseSettings)
    {
        $this->configFile = &$configFile;
        $this->useDatabaseSettings = &$useDatabaseSettings;
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
