<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;
use Request;

class Stringwcolour extends String_
{

    public function display($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    public function edit($content)
    {
        $this->_editViewData['selectOptions'] = array('none' => 'No Colour');
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        $this->_editViewData['selectClass'] = 'select_colour';
        return parent::edit($this->_defaultData($content));
    }

    public function submit($postDataKey = '')
    {
        $colourBlocks = Request::input($postDataKey . $this->_editClass . '_colour');
        if ($textBlocks = Request::input($postDataKey . $this->_editClass)) {
            foreach ($textBlocks as $blockId => $textBlock) {
                $content = new \stdClass;
                $content->text = $textBlock;
                $content->colour = !empty($colourBlocks[$blockId]) ? $colourBlocks[$blockId] : '';
                $this->_block->id = $blockId;
                $this->save($content);
            }
        }
    }

    public function save($content)
    {
        $content = (!$content || (empty($content->text) && empty($content->colour))) ? '' : serialize($content);
        return parent::save($content);
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->text = !empty($content->text) ? $content->text : 0;
        $content->colour = !empty($content->colour) ? $content->colour : 0;
        return $content;
    }

    public function search_text($content)
    {
        $content = $this->_defaultData($content);
        $searchText = ($content->text ?: '') . ($content->colour ?: '');
        return !empty($searchText) ? strip_tags($searchText) : null;
    }

}