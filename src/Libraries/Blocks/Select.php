<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Select extends _Base
{

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $options = array();
        $select_opts = BlockSelectOption::where('block_id', '=', $block->id)->get();
        foreach ($select_opts as $opts) {
            $options[$opts->value] = $opts->option;
        }
        $field_data = new \stdClass;
        $field_data->options = $options;
        $field_data->selected = $block_data;
        if (preg_match('/^#[a-f0-9]{6}$/i', key($options))) {
            $field_data->class = "select_colour";
        }
        self::$edit_id = array($block->id);
        return $field_data;
    }

    public static function block_settings_action()
    {
        return ['action' => 'themes/selects', 'name' => 'Manage block select options'];
    }

}