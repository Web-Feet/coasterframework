<?php namespace CoasterCms\Events\Admin;

class LoadResponse
{
    /**
     * @var string
     */
    public $layout;
    
    /**
     * @var array
     */
    public $layoutData;
    
    /**
     * @var string
     */
    public $altResponseContent;
    
    /**
     * @var int
     */
    public $responseCode;

    /**
     * LoadResponse constructor.
     * @param string $layout
     * @param array $layoutData
     * @param string $altResponseContent
     * @param int $responseCode
     */
    public function __construct(&$layout, &$layoutData, &$altResponseContent, &$responseCode)
    {
        $this->layout = &$layout;
        $this->layoutData = &$layoutData;
        $this->altResponseContent = &$altResponseContent;
        $this->responseCode = &$responseCode;
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
