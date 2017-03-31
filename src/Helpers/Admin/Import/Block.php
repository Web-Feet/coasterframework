<?php namespace CoasterCms\Helpers\Admin\Import;

class Block
{

    /**
     * @var array
     */
    public $blockData;

    /**
     * @var array
     */
    public $globalData;

    /**
     * @var array
     */
    public $templates;

    /**
     * @var array
     */
    public $inRepeaterBlocks;

    /**
     * @var array
     */
    public $repeaterChildBlocks;

    /**
     * @var array
     */
    public $inCategoryTemplates;

    /**
     * @var array
     */
    public $specifiedPageIds;

    /**
     * Block constructor.
     * @param array $blockData
     */
    public function __construct($blockData = [])
    {
        $this->blockData = $blockData;
        $this->globalData = [];
        $this->templates = [];
        $this->inRepeaterBlocks = [];
        $this->repeaterChildBlocks = [];
        $this->inCategoryTemplates = [];
        $this->specifiedPageIds = [];
    }

    /**
     * @param array $blockData
     * @param bool $overwrite
     */
    public function setBlockData($blockData = [], $overwrite = false)
    {
        $this->_setArrayData($this->blockData, $blockData, $overwrite);
    }

    /**
     * @param array $globalData
     * @param bool $overwrite
     */
    public function setGlobalData($globalData = [], $overwrite = false)
    {
        $this->_setArrayData($this->globalData, $globalData, $overwrite);
    }

    /**
     * @param array|string $templates
     */
    public function addTemplates($templates = [])
    {
        $this->_addToUniqueArray($this->templates, $templates);
    }

    /**
     * @param array|string $repeaterBlocks
     */
    public function addRepeaterBlocks($repeaterBlocks = [])
    {
        $this->_addToUniqueArray($this->inRepeaterBlocks, $repeaterBlocks);
    }

    /**
     * @param array|string $repeaterChildBlocks
     */
    public function addRepeaterChildBlocks($repeaterChildBlocks = [])
    {
        $this->_addToUniqueArray($this->repeaterChildBlocks, $repeaterChildBlocks);
    }

    /**
     * @param array|string $categoryTemplates
     */
    public function addCategoryTemplates($categoryTemplates = [])
    {
        $this->_addToUniqueArray($this->inCategoryTemplates, $categoryTemplates);
    }

    /**
     * @param array|string $specifiedPageIds
     */
    public function addSpecifiedPageIds($specifiedPageIds = [])
    {
        $this->_addToUniqueArray($this->specifiedPageIds, $specifiedPageIds);
    }

    /**
     * Add extra data from second block
     * @param Block $block
     */
    public function combine(Block $block)
    {
        $this->setGlobalData($block->blockData);
        $this->setBlockData($block->globalData);
        $this->addTemplates($block->templates);
        $this->addRepeaterBlocks($block->inRepeaterBlocks);
        $this->addRepeaterChildBlocks($block->repeaterChildBlocks);
        $this->addCategoryTemplates($block->inCategoryTemplates);
        $this->addSpecifiedPageIds($block->specifiedPageIds);
    }

    /**
     * @param array $array
     * @param array|string $items
     */
    protected function _addToUniqueArray(&$array, $items)
    {
        $items = is_array($items) ? $items : [$items];
        $array = array_unique(array_merge($array, $items));
    }

    /**
     * @param array $array
     * @param array $newData
     * @param bool $overwrite
     */
    protected function _setArrayData(&$array, $newData, $overwrite)
    {
        $array = $overwrite ? array_merge($array, $newData) : array_merge($newData, $array);
    }

}