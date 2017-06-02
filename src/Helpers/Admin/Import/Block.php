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
     * @return $this
     */
    public function setBlockData($blockData = [], $overwrite = false)
    {
        unset($blockData['created_at']);
        unset($blockData['updated_at']);
        $this->_setArrayData($this->blockData, $blockData, $overwrite);
        return $this;
    }

    /**
     * @param array $globalData
     * @param bool $overwrite
     * @return $this
     */
    public function setGlobalData($globalData = [], $overwrite = false)
    {
        $globalData = array_intersect_key($globalData, array_fill_keys(['show_in_global', 'show_in_pages'], ''));
        $this->_setArrayData($this->globalData, $globalData, $overwrite);
        return $this;
    }

    /**
     * @param array|string $templates
     * @return $this
     */
    public function addTemplates($templates = [])
    {
        $this->_addToUniqueArray($this->templates, $templates);
        return $this;
    }

    /**
     * @param array|string $repeaterBlocks
     * @return $this
     */
    public function addRepeaterBlocks($repeaterBlocks = [])
    {
        $this->_addToUniqueArray($this->inRepeaterBlocks, $repeaterBlocks);
        return $this;
    }

    /**
     * @param array|string $repeaterChildBlocks
     * @return $this
     */
    public function addRepeaterChildBlocks($repeaterChildBlocks = [])
    {
        $this->_addToUniqueArray($this->repeaterChildBlocks, $repeaterChildBlocks);
        return $this;
    }

    /**
     * @param array|string $categoryTemplates
     * @return $this
     */
    public function addCategoryTemplates($categoryTemplates = [])
    {
        $this->_addToUniqueArray($this->inCategoryTemplates, $categoryTemplates);
        return $this;
    }

    /**
     * @param array|string $specifiedPageIds
     * @return $this
     */
    public function addSpecifiedPageIds($specifiedPageIds = [])
    {
        $this->_addToUniqueArray($this->specifiedPageIds, $specifiedPageIds);
        return $this;
    }

    /**
     * Add extra data from second block
     * @param Block $block
     */
    public function combine(Block $block)
    {
        $this->setBlockData($block->blockData, false);
        $this->setGlobalData($block->globalData, false);
        $this->addTemplates($block->templates);
        $this->addRepeaterBlocks($block->inRepeaterBlocks);
        $this->addRepeaterChildBlocks($block->repeaterChildBlocks);
        $this->addCategoryTemplates($block->inCategoryTemplates);
        $this->addSpecifiedPageIds($block->specifiedPageIds);
    }

    /**
     * Find any differences in the data
     * @param Block $block
     * @return bool
     */
    public function subtract(Block $block)
    {
        $this->blockData = array_diff_assoc($this->blockData, $block->blockData);
        $this->globalData = array_diff_assoc($this->globalData, $block->globalData);
        $this->templates = array_diff($this->templates, $block->templates);
        $this->inRepeaterBlocks = array_diff($this->inRepeaterBlocks, $block->inRepeaterBlocks);
        $this->repeaterChildBlocks = array_diff($this->repeaterChildBlocks, $block->repeaterChildBlocks);
        $this->inCategoryTemplates = array_diff($this->inCategoryTemplates, $block->inCategoryTemplates);
        $this->specifiedPageIds = array_diff($this->specifiedPageIds, $block->specifiedPageIds);
        foreach ($this as $property) {
            if ($property) {
                return true; // has diff data
            }
        }
        return false;
    }

    /**
     * @param array $array
     * @param array|string $items
     */
    protected function _addToUniqueArray(&$array, $items)
    {
        $items = is_array($items) ? array_values($items) : [$items];
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