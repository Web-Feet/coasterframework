<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Page;

class Selectpage extends _Base
{

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $list_options = array();
        $parent = BlockSelectOption::where('block_id', '=', $block->id)->first();
        if (!empty($parent)) {
            $list_options['parent'] = $parent->value;
        }
        $field_data = new \stdClass;
        $field_data->pages_array = array(0 => '-- No Page Selected --') + Page::get_page_list($list_options);
        $field_data->selected = $block_data;
        self::$edit_id = array($block->id);
        return $field_data;
    }

}