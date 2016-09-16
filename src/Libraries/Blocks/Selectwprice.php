<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;
use Request;

class Selectwprice extends String_
{

    public function display($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    public function edit($content)
    {
        $this->_editViewData['selectOptions'] = [];
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        if (preg_match('/^#[a-f0-9]{6}$/i', key($selectOptions))) {
            $this->_editViewData['class'] = 'select_colour';
        }
        return parent::edit($this->_defaultData($content));
    }

    public function submit($postDataKey = '')
    {
        $selectBlocks = Request::input($postDataKey . $this->_editClass);
        if ($priceBlocks = Request::input($postDataKey . $this->_editClass . '_price')) {
            foreach ($priceBlocks as $blockId => $priceBlock) {
                $content = new \stdClass;
                $content->selected = !empty($selectBlocks[$blockId]) ? $selectBlocks[$blockId] : '';
                $content->price = $priceBlock;
                $this->_block->id = $blockId;
                $this->save($content);
            }
        }
    }

    public function save($content)
    {
        $content = (!$content || (empty($content->selected) && empty($content->price))) ? '' : serialize($content);
        return parent::save($content);
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->selected = !empty($content->selected) ? $content->selected : 0;
        $content->price = !empty($content->price) ? $content->price : 0;
        return $content;
    }

    public function search_text($content)
    {
        $content = $this->_defaultData($content);
        $searchText = ($content->selected ?: '') . ($content->price ?: '');
        return !empty($searchText) ? strip_tags($searchText) : null;
    }

}