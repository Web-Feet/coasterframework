<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\BlockManager;
use CoasterCms\Models\BlockSelectOption;
use Request;

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

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        // check for empty selects using a block_exists field
        $check_for_empty_selects = Request::input($blocks_key . '_exists');
        if (!empty($check_for_empty_selects)) {
            foreach ($check_for_empty_selects as $block_id => $v) {
                if (Request::input($blocks_key . '.' . $block_id) == null) {
                    BlockManager::update_block($block_id, '', $page_id, $repeater_info);
                }
            }
        }
    }

    public static function block_settings_action()
    {
        return ['action' => 'themes/selects', 'name' => 'Manage block select options'];
    }

}