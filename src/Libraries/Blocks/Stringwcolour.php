<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

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

    public function save($content)
    {
        if ($content && (!empty($content['text']) || !empty($content['colour']))) {
            $saveData = new \stdClass;
            $saveData->selected = !empty($content['text']) ? $content['text'] : '';
            $saveData->colour = !empty($content['colour']) ? $content['colour'] : '';
        }
        return parent::save(isset($saveData) ? serialize($saveData) : '');
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->text = !empty($content->text) ? $content->text : '';
        $content->colour = !empty($content->colour) ? $content->colour : '';
        return $content;
    }

    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        return $this->_generateSearchText($content->text, $content->colour);
    }

}