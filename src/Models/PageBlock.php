<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class PageBlock extends Eloquent
{
    use DataPreLoad;

    protected $table = 'page_blocks';
    protected static $_preloadDone = [];

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

    public static function updateBlockData($content, $blockId, $versionId, $pageId)
    {
        $previousData = static::where('block_id', '=', $blockId)->where('page_id', '=', $pageId)->where('language_id', '=', Language::current())->orderBy('version', 'desc')->first();
        if (!empty($previousData) && $previousData->version > $versionId) {
            throw new \Exception('VersionId ('.$versionId.') for the new data must be higher than the previous versionId ('.$previousData->version.')!');
        }
        if (empty($previousData) || (!empty($previousData) && $previousData->content !== $content)) {
            $block = new static;
            $block->block_id = $blockId;
            $block->page_id = $pageId;
            $block->language_id = Language::current();
            $block->content = $content;
            $block->version = $versionId;
            $block->save();
        }
    }

    public static function get_block($block_id, $page_id, $repeater_info, $version)
    {
        $selectedBlockQuery = static::where('block_id', '=', $block_id)->where('page_id', '=', $page_id)->where('language_id', '=', Language::current());
        if (!empty($version)) {
            $selectedBlockQuery = $selectedBlockQuery->where('version', '<=', $version);
        }
        $selectedBlock = $selectedBlockQuery->orderBy('version', 'desc')->first();
        return !empty($selectedBlock) ? $selectedBlock->content : null;
    }

    public static function preload_page($page_id, $version = 0)
    {
        self::_preloadDataByPage($page_id, $version);
        return static::_preloadGet('byVersionPage', [$version, $page_id]) ?: [];
    }

    public static function preload_block($page_id, $block_id, $version = 0, $preloadBy = 'block_id')
    {
        if ($preloadBy == 'block_id') {
            self::_preloadDataByBlock($block_id, $version);
        } else {
            self::_preloadDataByPage($page_id, $version);
        }
        return static::_preloadGet('byVersionPage', [$version, $page_id, $block_id]) ?: [];
    }

    protected static function _preloadDataByPage($page_id, $version)
    {
        $doneKey = 'v'.$version.'p'.$page_id;
        if (empty(static::$_preloadDone[$doneKey])) {
            static::$_preloadDone[$doneKey] = true;
            $page_blocks = BlockManager::get_data_for_version(new static, $version, ['page_id'], [$page_id], 'block_id');

            static::_preload($page_blocks, 'byVersionPage', [['@'.$version, 'page_id', 'block_id', 'language_id']]);

        }
    }

    protected static function _preloadDataByBlock($block_id, $version)
    {
        $doneKey = 'v'.$version.'b'.$block_id;
        if (empty(static::$_preloadDone[$doneKey])) {
            static::$_preloadDone[$doneKey] = true;
            $page_blocks = BlockManager::get_data_for_version(new static, $version, ['block_id'], [$block_id], 'block_id');
            static::_preload($page_blocks, 'byVersionPage', [['@'.$version, 'page_id', 'block_id', 'language_id']]);
        }
    }

    public static function page_blocks_on_live_page_versions($block_id, $reload = false)
    {
        if ($reload) {
            static::_preloadClear('liveBlocks');
        }
        if (!static::_preloadIsset('liveBlocks')) {
            $page_blocks = BlockManager::get_data_for_version(new static, -1, ['block_id', 'language_id'], [$block_id, Language::current()], 'block_id');
            static::_preload($page_blocks, 'liveBlocks', [['block_id']], null, true);
        }
        return static::_preloadGet('liveBlocks', $block_id) ?: [];
    }

}