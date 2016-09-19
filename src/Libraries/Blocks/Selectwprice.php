<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

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

    public function save($content)
    {
        if ($content && (!empty($content['select']) || !empty($content['price']))) {
            $saveData = new \stdClass;
            $saveData->selected = !empty($content['select']) ? $content['select'] : 0;
            $saveData->price = !empty($content['price']) ? $content['price'] : 0;
        }
        return parent::save(isset($saveData) ? serialize($saveData) : '');
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

    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        return $this->_generateSearchText($content->selected, $content->price);
    }

}