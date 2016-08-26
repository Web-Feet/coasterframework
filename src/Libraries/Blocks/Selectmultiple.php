<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\PageBlock;

class Selectmultiple extends _Base
{

    public static function display($block, $block_data, $options = null)
    {
        if (isset($options['returnAll']) && $options['returnAll']) {
            return BlockSelectOption::getOptionsArray($block->id);
        }
        if (!empty($block_data)) {
            return unserialize($block_data);
        } else {
            return [];
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $options = array();
        $select_opts = BlockSelectOption::where('block_id', '=', $block->id)->get();
        foreach ($select_opts as $opts) {
            $options[$opts->value] = $opts->option;
        }
        $field_data = new \stdClass;
        $field_data->options = $options;
        $field_data->selected = @unserialize($block_data);
        if (preg_match('/^#[a-f0-9]{6}$/i', key($options))) {
            $field_data->class = "select_colour";
        }
        self::$edit_id = array($block->id);
        return $field_data;
    }

    public static function save($block_content)
    {
        if (!empty($block_content)) {
            return serialize($block_content);
        } else {
            return '';
        }
    }

    public static function filter($block_id, $search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($block_id);
        $page_ids = array();
        if (!empty($live_blocks) && $search) {
            foreach ($live_blocks as $live_block) {
                $items = !empty($live_block->content) ? unserialize($live_block->content) : array();
                switch ($type) {
                    case '=':
                        if (in_array($search, $items)) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        foreach ($items as $item) {
                            if (strpos($item, $search) !== false) {
                                $page_ids[] = $live_block->page_id;
                            }
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

}
