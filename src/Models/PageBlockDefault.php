<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class PageBlockDefault extends Eloquent
{
    use DataPreLoad;

    protected $table = 'page_blocks_default';

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

    /**
     * @return array
     */
    protected static function _preloadByColumn()
    {
        return [['block_id', 'language_id']];
    }

}