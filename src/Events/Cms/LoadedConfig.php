<?php namespace CoasterCms\Events\Cms;

class LoadedConfig
{
    /**
     * @var string
     */
    public $configValues;

    /**
     * LoadedConfig constructor.
     * @param array $configValues
     */
    public function __construct(&$configValues)
    {
        $this->configValues = &$configValues;
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
