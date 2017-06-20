<?php namespace CoasterCms\Events\Cms;

use CoasterCms\Models\Block;

class SubmitFormData
{
    /**
     * @var Block
     */
    public $block;

    /**
     * @var array
     */
    public $formData;

    /**
     * LoadConfig constructor.
     * @param Block $block
     * @param array $formData
     */
    public function __construct(&$block, &$formData)
    {
        $this->block = &$block;
        $this->formData = &$formData;
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
