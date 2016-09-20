<?php namespace CoasterCms\Models;

use Eloquent;

class PageBlockDefault extends Eloquent
{

    protected $table = 'page_blocks_default';
    public static $preloaded_lang_default = false;

    public static function updateBlockData($content, $blockId, $versionId)
    {
        $previousData = static::where('block_id', '=', $blockId)->where('language_id', '=', Language::current())->orderBy('version', 'desc')->first();
        if (!empty($previousData) && $previousData->version > $versionId) {
            throw new \Exception('VersionId ('.$versionId.') for the new data must be higher than the previous versionId ('.$previousData->version.')!');
        }
        if (empty($previousData) || (!empty($previousData) && $previousData->content !== $content)) {
            $block = new static;
            $block->block_id = $blockId;
            $block->language_id = Language::current();
            $block->content = $content;
            $block->version = $versionId;
            $block->save();
        }
    }

    public static function getBlockData($blockId, $versionId)
    {
        $getDataQuery = static::where('block_id', '=', $blockId)->where('language_id', '=', Language::current());
        if (!empty($versionId)) {
            $getDataQuery = $getDataQuery->where('version', '<=', $versionId);
        }
        $blockData = $getDataQuery->orderBy('version', 'desc')->first();
        return $blockData ? $blockData->content : null;
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
            $default_blocks = Block::getDataForVersion(new self, $version, null, null, 'block_id');
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