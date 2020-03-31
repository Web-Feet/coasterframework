<?php namespace CoasterCms\Models;

use Eloquent;

class PageBlockRepeaterData extends Eloquent
{

    protected $table = 'page_blocks_repeater_data';

    public static function updateBlockData($content, $blockId, $versionId, $repeaterId, $repeaterRowId)
    {
        if (!($repeaterRowKey = PageBlockRepeaterRows::getRowKey($repeaterId, $repeaterRowId))) {
            $repeaterRowKey = PageBlockRepeaterRows::addRowKey($repeaterId, $repeaterRowId);
        }
        $previousData = static::where('row_key', '=', $repeaterRowKey)->where('block_id', '=', $blockId)->orderBy('version', 'desc')->first();
        if (!empty($previousData) && $previousData->version > $versionId) {
            throw new \Exception('VersionId ('.$versionId.') for the new data must be higher than the previous versionId ('.$previousData->version.')!');
        }
        if (empty($previousData) || (!empty($previousData) && $previousData->content !== $content)) {
            $block = new static;
            $block->block_id = $blockId;
            $block->row_key = $repeaterRowKey;
            $block->content = $content;
            $block->version = $versionId;
            $block->save();
        }
    }

    public static function getBlockData($blockId, $versionId, $repeaterId, $repeaterRowId)
    {
        $repeaterRowKey = PageBlockRepeaterRows::getRowKey($repeaterId, $repeaterRowId);
        $getDataQuery = static::where('block_id', '=', $blockId)->where('row_key', '=', $repeaterRowKey)->where('language_id', '=', Language::current());
        if (!empty($versionId)) {
            $getDataQuery = $getDataQuery->where('version', '<=', $versionId);
        }
        $blockData = $getDataQuery->orderBy('version', 'desc')->first();
        return $blockData ? $blockData->content : null;
    }

    public static function loadRepeaterData($repeaterId, $version = 0, $randomOrder = false)
    {
        $repeaterRowsById = [];
        if ($rowIds = PageBlockRepeaterRows::getRowIds($repeaterId)) {
            $repeaterRowsData = Block::getDataForVersion(new static, $version, ['row_key' => array_keys($rowIds)]) ?: [];
            foreach ($repeaterRowsData as $repeaterRowData) {
                $repeaterRowId = $rowIds[$repeaterRowData->row_key];
                if (!isset($repeaterRowsById[$repeaterRowId])) {
                    $repeaterRowsById[$repeaterRowId] = [];
                }
                $repeaterRowsById[$repeaterRowId][$repeaterRowData->block_id] = $repeaterRowData->content;
            }
            if ($randomOrder === false) {
                uasort($repeaterRowsById, ['self', '_order']);
            } else {
                shuffle($repeaterRowsById);
                $repeaterRowsById = $randomOrder === true ? $repeaterRowsById : array_slice($repeaterRowsById, 0, $randomOrder);
            }
        }
        return $repeaterRowsById;
    }

    protected static function _order($a, $b)
    {
        if (!isset($a[0])) {
            return 1;
        }
        if (!isset($b[0])) {
            return -1;
        }
        if ($a[0] == $b[0]) {
            return 0;
        }
        return ($a[0] < $b[0]) ? -1 : 1;
    }

}
