<?php namespace CoasterCms\Models;

use Eloquent;

class PageBlockRepeaterData extends Eloquent
{

    protected $table = 'page_blocks_repeater_data';

    public static function updateBlockData($content, $blockId, $versionId, $repeaterId, $repeaterRowId)
    {
        if (!($repeaterRowKey = PageBlockRepeaterRows::get_row_key($repeaterId, $repeaterRowId))) {
            $repeaterRowKey = PageBlockRepeaterRows::add_row_key($repeaterId, $repeaterRowId)->id;
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
        $repeaterRowKey = PageBlockRepeaterRows::get_row_key($repeaterId, $repeaterRowId);
        $getDataQuery = static::where('block_id', '=', $blockId)->where('row_key', '=', $repeaterRowKey)->where('language_id', '=', Language::current());
        if (!empty($versionId)) {
            $getDataQuery = $getDataQuery->where('version', '<=', $versionId);
        }
        $blockData = $getDataQuery->orderBy('version', 'desc')->first();
        return $blockData ? $blockData->content : null;
    }

    public static function get_sub_repeater_data($repeater_id, $version = 0)
    {
        $sub_repeater_data = [];
        $row_keys_objects = PageBlockRepeaterRows::get_row_objects($repeater_id);
        $row_keys = [];
        $row_key_data = [];
        foreach ($row_keys_objects as $row_keys_object) {
            $row_keys[] = $row_keys_object->id;
            $row_key_data[$row_keys_object->id] = $row_keys_object;
        }
        $repeater_data = Block::getDataForVersion(new self, $version, ['row_key'], [$row_keys]);
        if (!empty($repeater_data)) {
            $repeater_blocks = Block::getBlockIdsOfType('repeater');
            foreach ($repeater_data as $repeater) {
                $repeater->row_id = $row_key_data[$repeater->row_key]->row_id;
                $repeater->repeater_id = $row_key_data[$repeater->row_key]->repeater_id;
                $sub_repeater_data[] = $repeater;
                if (in_array($repeater->block_id, $repeater_blocks)) {
                    $sub_repeater_data = array_merge(self::get_sub_repeater_data($repeater->content, $version), $sub_repeater_data);
                }
            }
        }
        return $sub_repeater_data;
    }

    public static function get_page_repeater_data($page_id, $version = 0)
    {
        $organised_data = [];
        $repeater_block_ids = Block::getBlockIdsOfType('repeater');
        if (!empty($repeater_block_ids)) {
            if (!empty($page_id)) {
                $repeater_blocks = Block::getDataForVersion(new PageBlock, $version, ['block_id', 'page_id'], [$repeater_block_ids, $page_id]);
            } else {
                $repeater_blocks = Block::getDataForVersion(new PageBlockDefault, $version, ['block_id'], [$repeater_block_ids]);
            }
            if (!empty($repeater_blocks)) {
                $repeater_data = [];
                foreach ($repeater_blocks as $repeater_block) {
                    $sub_repeater_data = self::get_sub_repeater_data($repeater_block->content, $version);
                    if (empty($sub_repeater_data)) {
                        $empty_repeater = new \stdClass;
                        $empty_repeater->repeater_id = $repeater_block->content;
                        $sub_repeater_data = [$empty_repeater];
                    }
                    $repeater_data = array_merge($sub_repeater_data, $repeater_data);
                }
                foreach ($repeater_data as $repeater_block) {
                    if (!isset($organised_data[$repeater_block->repeater_id])) {
                        $organised_data[$repeater_block->repeater_id] = [];
                    }
                    if (isset($repeater_block->row_id)) {
                        if (!isset($organised_data[$repeater_block->repeater_id][$repeater_block->row_id])) {
                            $organised_data[$repeater_block->repeater_id][$repeater_block->row_id] = [];
                        }
                        $organised_data[$repeater_block->repeater_id][$repeater_block->row_id][] = $repeater_block;
                    }
                }
            }
        }
        return $organised_data;
    }

    public static function load_by_repeater_id($repeater_id, $version = 0, $randomLimit = false)
    {
        $repeater_rows = array();
        $row_keys = [];
        $row_keys_objects = PageBlockRepeaterRows::get_row_objects($repeater_id, $randomLimit);
        $row_keys_data = [];
        foreach ($row_keys_objects as $row_keys_object) {
            $row_keys[] = $row_keys_object->id;
            $row_keys_data[$row_keys_object->id] = $row_keys_object;
        }
        $repeaters_data = Block::getDataForVersion(new self, $version, ['row_key'], [$row_keys]);
        if (!empty($repeaters_data)) {
            foreach ($repeaters_data as $repeater_data) {
                $repeater_data->row_id = $row_keys_data[$repeater_data->row_key]->row_id;
                $repeater_data->repeater_id = $row_keys_data[$repeater_data->row_key]->repeater_id;
                if (!isset($repeater_rows[$repeater_data->row_id])) {
                    $repeater_rows[$repeater_data->row_id] = array();
                }
                $repeater_rows[$repeater_data->row_id][$repeater_data->block_id] = $repeater_data->content;
            }
        }
        if ($randomLimit === false) {
            uasort($repeater_rows, array('self', '_order'));
        }
        return $repeater_rows;
    }

    public static function loadRepeaterData($repeaterId, $version = 0, $randomOrder = false)
    {
        $repeaterRowsById = [];
        if ($rowKeysObjects = PageBlockRepeaterRows::get_row_objects($repeaterId, $randomOrder)) {
            $rowKeyToId = [];
            foreach ($rowKeysObjects as $rowId => $rowKeysObject) {
                $rowKeyToId[$rowKeysObject->id] = $rowId;
            }
            $repeaterRowsData = Block::getDataForVersion(new static, $version, ['row_key'], [array_keys($rowKeyToId)]) ?: [];
            foreach ($repeaterRowsData as $repeaterRowData) {
                $repeaterRowId = $rowKeyToId[$repeaterRowData->row_key];
                if (!isset($repeaterRowsById[$repeaterRowId])) {
                    $repeaterRowsById[$repeaterRowId] = [];
                }
                $repeaterRowsById[$repeaterRowId][$repeaterRowData->block_id] = $repeaterRowData;
            }
            if ($randomOrder === false) {
                uasort($repeaterRowsById, ['self', '_order']);
            }
        }
        return $repeaterRowsById;
    }

    private static function _order($a, $b)
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

    public static function next_free_repeater_id()
    {
        $last = PageBlockRepeaterRows::orderBy('repeater_id', 'desc')->first();
        if (!empty($last)) {
            return PageBlockRepeaterRows::add_row_key($last->repeater_id + 1, 0)->repeater_id;
        } else {
            return PageBlockRepeaterRows::add_row_key(1, 0)->repeater_id;
        }
    }

    public static function next_free_row_id($repeater_id)
    {
        $last = PageBlockRepeaterRows::where('repeater_id', '=', $repeater_id)->orderBy('row_id', 'desc')->first();
        if (!empty($last)) {
            if ($last->row_id == 0) {
                $last->row_id = 1;
                $last->save();
                return 1;
            } else {
                return PageBlockRepeaterRows::add_row_key($repeater_id, $last->row_id + 1)->row_id;
            }
        } else {
            return PageBlockRepeaterRows::add_row_key($repeater_id, 1)->row_id;
        }
    }

}
