<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;
use Request;

class Select extends String_
{

    public function display($content, $options = [])
    {
        if (isset($options['returnAll']) && $options['returnAll']) {
            return BlockSelectOption::getOptionsArray($this->_block->id);
        }
        return $content;
    }

    public function edit($content)
    {
        $this->_editExtraViewData['selectOptions'] = [];
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editExtraViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        if (preg_match('/^#[a-f0-9]{6}$/i', key($options))) {
            $this->_editExtraViewData['class'] = "select_colour";
        }
        return parent::edit($content);
    }

    public function submit($postDataKey = '')
    {
        $this->submit($postDataKey);
        if ($submittedSelects = Request::input($postDataKey . $this->_editClass . '_exists')) {
            $selectsWithValues = Request::input($postDataKey . $this->_editClass);
            foreach ($submittedSelects as $blockId => $value) {
                if (array_key_exists($blockId, $selectsWithValues)) {
                    $this->_block->id = $blockId;
                    $this->save('');
                }
            }
        }
    }

    public static function block_settings_action()
    {
        return ['action' => 'themes/selects', 'name' => 'Manage block select options'];
    }

}