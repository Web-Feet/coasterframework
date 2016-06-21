<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Core\BlockManager;
use Eloquent;

class PageBlock extends Eloquent
{

    protected $table = 'page_blocks';
    private static $page_blocks = [];
    private static $block = [];

    public static function restore($obj)
    {
        $obj->save();
    }

    public static function update_block($block_id, $content, $page_id, $repeater_info = null)
    {
        $updated_block = self::where('block_id', '=', $block_id)->where('page_id', '=', $page_id)->where('language_id', '=', Language::current())->orderBy('version', 'desc')->first();

        $to_version = BlockManager::$to_version;
        if (empty($to_version)) {
            $new_version = PageVersion::add_new($page_id);
            $to_version = $new_version->version_id;
        }
        if (empty($updated_block) || (!empty($updated_block) && $updated_block->content !== $content)) {
            $block = new self;
            $block->block_id = $block_id;
            $block->page_id = $page_id;
            $block->language_id = Language::current();
            $block->content = $content;
            $block->version = $to_version;

            $block->save();
        }
    }

    public static function get_block($block_id, $page_id, $repeater_info, $version)
    {
        $selected_block = self::where('block_id', '=', $block_id)->where('page_id', '=', $page_id)->where('language_id', '=', Language::current());
        if (empty($version)) {
            $selected_block = $selected_block->orderBy('version', 'desc')->first();
        } else {
            $selected_block = $selected_block->where('version', '<=', $version)->orderBy('version', 'desc')->first();
        }
        if (!empty($selected_block)) {
            return $selected_block->content;
        } else {
            return null;
        }
    }

    // load all page block content for a page
    public static function preload_page($page_id, $version = 0)
    {
        self::_preload_page($page_id, $version);
        if (!empty(self::$page_blocks[$version][$page_id]))
            return self::$page_blocks[$version][$page_id];
        else
            return [];
    }

    // load page block content (pre-load & cache all data for block id)
    public static function preload_block($page_id, $block_id, $version = 0)
    {
        self::_preload_block($block_id, $version);
        if (!empty(self::$page_blocks[$version][$page_id][$block_id]))
            return self::$page_blocks[$version][$page_id][$block_id];
        else
            return [];
    }

    // load page block content (pre-load & cache all data for page id)
    public static function preload_page_block($page_id, $block_id, $version = 0)
    {
        self::_preload_page($page_id, $version);
        if (!empty(self::$page_blocks[$version][$page_id][$block_id]))
            return self::$page_blocks[$version][$page_id][$block_id];
        else
            return [];
    }

    private static function _preload_page($page_id, $version)
    {
        if (!isset(self::$page_blocks[$version])) {
            self::$page_blocks[$version] = [];
        }
        if (!isset(self::$page_blocks[$version][$page_id])) {
            self::$page_blocks[$version][$page_id] = [];
            $page_blocks = BlockManager::get_data_for_version(new self, $version, array('page_id'), array($page_id), 'block_id');
            if (!empty($page_blocks)) {
                foreach ($page_blocks as $page_block) {
                    if (!isset(self::$page_blocks[$version][$page_block->page_id][$page_block->block_id])) {
                        self::$page_blocks[$version][$page_block->page_id][$page_block->block_id] = [];
                    }
                    self::$page_blocks[$version][$page_block->page_id][$page_block->block_id][$page_block->language_id] = $page_block;
                }
            }
        }
    }

    private static function _preload_block($block_id, $version)
    {
        if (!isset(self::$page_blocks[$version])) {
            self::$page_blocks[$version] = [];
        }
        if (!isset(self::$block[$version][$block_id])) {
            self::$block[$version][$block_id] = true;
            $page_blocks = BlockManager::get_data_for_version(new self, $version, array('block_id'), array($block_id), 'page_id');
            if (!empty($page_blocks)) {
                foreach ($page_blocks as $page_block) {
                    if (!isset(self::$page_blocks[$version][$page_block->page_id])) {
                        self::$page_blocks[$version][$page_block->page_id] = [];
                    }
                    if (!isset(self::$page_blocks[$version][$page_block->page_id][$page_block->block_id])) {
                        self::$page_blocks[$version][$page_block->page_id][$page_block->block_id] = [];
                    }
                    self::$page_blocks[$version][$page_block->page_id][$page_block->block_id][$page_block->language_id] = $page_block;
                }
            }
        }
    }

    public static function page_blocks_on_live_page_versions($block_id)
    {
        return BlockManager::get_data_for_version(
            new self,
            -1,
            array('block_id', 'language_id'),
            array($block_id, Language::current()),
            'page_id'
        );
    }

}