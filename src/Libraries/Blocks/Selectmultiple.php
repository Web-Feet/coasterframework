<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\BlockManager;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\PageBlock;
use Illuminate\Support\Facades\Request;

class Selectmultiple extends _Base
{

    public static function display($block, $block_data, $options = null)
    {
        return unserialize($block_data);
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
        return serialize($block_content);
    }

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        // check for empty multiple selects with hidden block_exists field
        $check_for_empty_multiple_selects = Request::input($blocks_key . '_exists');
        if (!empty($check_for_empty_multiple_selects)) {
            foreach ($check_for_empty_multiple_selects as $block_id => $v) {
                if (empty($updated_text_blocks[$block_id]) && Request::input($blocks_key . '.' . $block_id) == null) {
                    $block = Block::preload($block_id);
                    $block_class = __NAMESPACE__ . '\\' . ucwords($block->type);
                    $block_content = $block_class::save(array());
                    BlockManager::update_block($block_id, $block_content, $page_id, $repeater_info);
                }
            }
        }
    }

    public static function filter($block_id, $search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($block_id);
        $page_ids = array();
        if (!empty($live_blocks)) {
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