<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Core\BlockManager;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use Request;
use URL;

class Link extends _Base
{
    public static $blocks_key = 'link';

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($block_data)) {
            try {
                $block_data = unserialize($block_data);
            } catch (\Exception $e) {
                $block_data = array('link' => '', 'target' => '');
            }
        } else {
            $block_data = array('link' => '', 'target' => '');
        }
        if (!empty($block_data['target'])) {
            $target = "\" target=\"{$block_data['target']}";
        } else {
            $target = "";
        }
        $link = str_replace('internal://', '', $block_data['link'], $count);
        if ($count > 0) {
            return PageLang::full_url($link) . $target;
        } else {
            return $link . $target;
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $content = new \stdClass;
        if (!empty($block_data)) {
            try {
                $block_data = unserialize($block_data);
            } catch (\Exception $e) {
                $block_data = array('link' => '', 'target' => '');
            }
        } else {
            $block_data = array('link' => '', 'target' => '');
        }
        $link = str_replace('internal://', '', $block_data['link'], $count);
        if ($count > 0) {
            $content->internal = $link;
            $content->external = '';
        } else {
            $content->internal = 0;
            $content->external = $link;
        }
        $content->target = !empty($block_data['target']) ? $block_data['target'] : 0;
        $content->target_options = array(0 => 'Target: Same Tab', '_blank' => 'Target: New Tab');
        $content->options = array(0 => 'Custom Link: ') + Page::get_page_list();
        self::$edit_id = array($block->id);
        return $content;
    }

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        $link_blocks = Request::input($blocks_key);
        if (!empty($link_blocks)) {
            foreach ($link_blocks as $block_id => $link) {
                $data = [];
                if (!empty($link['internal'])) {
                    $data['link'] = 'internal://' . $link['internal'];
                } elseif (!empty($link['custom'])) {
                    $data['link'] = $link['custom'];
                }
                if (!empty($link['target'])) {
                    $data['target'] = $link['target'];
                }
                if (!empty($data)) {
                    $block_content = serialize($data);
                } else {
                    $block_content = '';
                }
                BlockManager::update_block($block_id, $block_content, $page_id, $repeater_info);
            }
        }
    }

    public static function exportFiles($block, $block_data)
    {
        $doc = [];
        If (!empty($block_data)) {
            $block_data = unserialize($block_data);
            if (!empty($block_data['link'])) {
                if (strpos($block_data['link'], '/') === 0 || strpos($block_data['link'], URL::to('/')) === 0) {
                    $doc[] = str_replace(URL::to('/'), '', $block_data['link']);
                }
            }
        }
        return $doc;
    }

}