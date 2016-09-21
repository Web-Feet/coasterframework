<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Select extends String_
{
    public static $blockSettings = ['Manage block select options' => 'themes/selects'];

    public function display($content, $options = [])
    {
        if (isset($options['returnAll']) && $options['returnAll']) {
            return BlockSelectOption::getOptionsArray($this->_block->id);
        }
        return $content;
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
        return parent::edit($content);
    }

    public function save($content)
    {
        return parent::save(!empty($content['select']) ? $content['select'] : '');
    }

}