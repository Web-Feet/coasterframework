<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\BlockManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\DB;

class PageBlockDefault extends Eloquent
{

    protected $table = 'page_blocks_default';
    public static $preloaded_lang_default = false;

    public static function update_block($block_id, $content, $page_id = null, $repeater_info = null)
    {

        $updated_block = self::where('block_id', '=', $block_id)->where('language_id', '=', Language::current())->orderBy('version', 'desc')->first();

        $to_version = BlockManager::$to_version;
        if (empty($to_version)) {
            $new_version = PageVersion::add_new($page_id);
            $to_version = $new_version->version_id;
        }
        if (empty($updated_block) || (!empty($updated_block) && $updated_block->content != $content)) {
            $block = new self;
            $block->block_id = $block_id;
            $block->language_id = Language::current();
            $block->content = $content;
            $block->version = $to_version;
            $block->save();
        }
    }

    public static function get_block($block_id, $page_id, $repeater_info, $version)
    {
        $selected_block = self::where('block_id', '=', $block_id)->where('language_id', '=', Language::current());
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

    public static function preload_block($block_id, $version = 0)
    {
        self::_preload($version);
        if (!empty(self::$preloaded_lang_default[$block_id])) {
            return self::$preloaded_lang_default[$block_id];
        } else {
            return null;
        }
    }

    public static function preload($version = 0)
    {
        self::_preload($version);
        if (!empty(self::$preloaded_lang_default)) {
            return self::$preloaded_lang_default;
        } else {
            return null;
        }
    }

    private static function _preload($version)
    {
        if (self::$preloaded_lang_default === false) {
            self::$preloaded_lang_default = array();
            $table_name = DB::getTablePrefix() . (new self)->getTable();
            $default_blocks = BlockManager::get_data_for_version($table_name, $version, null, null, 'block_id');
            if (!empty($default_blocks)) {
                foreach ($default_blocks as $default_block) {
                    if (!isset(self::$preloaded_lang_default[$default_block->block_id])) {
                        self::$preloaded_lang_default[$default_block->block_id] = array();
                    }
                    self::$preloaded_lang_default[$default_block->block_id][$default_block->language_id] = $default_block;
                }
            }
        }
    }

}