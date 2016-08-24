<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use Request;

class Stringwprice extends _Base
{
    public static $blocks_key = 'blockp';

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($block_data)) {
            return unserialize($block_data);
        } else {
            $text = new \stdClass;
            $text->text = '';
            $text->price = 0;
            return $text;
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $field_data = new \stdClass;
        $block_data = unserialize($block_data);
        $field_data->text = !isset($block_data->text) ? '' : $block_data->text;
        $field_data->price = !isset($block_data->price) ? '' : $block_data->price;

        self::$edit_id = array($block->id);
        return $field_data;
    }

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        $text_blocks = Request::input($blocks_key);
        if (!empty($text_blocks)) {
            foreach ($text_blocks as $block_id => $block_content) {
                $text = new \stdClass;
                $text->text = $block_content;
                $text->price = Request::input($blocks_key . '_price.' . $block_id);
                if (empty($text->text) && empty($text->price)) {
                    $text = '';
                } else {
                    $text = serialize($text);
                }
                BlockManager::update_block($block_id, $text, $page_id, $repeater_info);
            }
        }
    }

    public static function search_text($block_content, $version = 0)
    {
        if (!empty($block_content)) {
            $block_content = unserialize($block_content);
            if (!empty($block_content->text)) {
                return strip_tags($block_content->text);
            }
        }
        return null;
    }

}