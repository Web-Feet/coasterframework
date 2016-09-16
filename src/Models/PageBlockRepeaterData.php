<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use Eloquent;

class PageBlockRepeaterData extends Eloquent
{

    protected $table = 'page_blocks_repeater_data';

    public static function update_block($block_id, $content, $page_id, $repeater_info = null)
    {
        $row_key = PageBlockRepeaterRows::get_row_key($repeater_info->repeater_id, $repeater_info->row_id);
        if (empty($row_key)) {
            $row_key = PageBlockRepeaterRows::add_row_key($repeater_info->repeater_id, $repeater_info->row_id)->id;
        }
        $updated_block = self::where('row_key', '=', $row_key)->where('block_id', '=', $block_id)->orderBy('version', 'desc')->first();
        $to_version = BlockManager::$to_version;
        if (empty($to_version)) {
            $new_version = PageVersion::add_new($page_id);
            $to_version = $new_version->version_id;
        }
        if (empty($updated_block) || (!empty($updated_block) && $updated_block->content !== $content)) {
            $block = new self;
            $block->block_id = $block_id;
            $block->row_key = $row_key;
            $block->content = $content;
            $block->version = $to_version;
            $block->save();
        }
    }

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

    public static function get_block($block_id, $repeater_id, $repeater_info, $version)
    {
        $row_key = PageBlockRepeaterRows::get_row_key($repeater_info->repeater_id, $repeater_info->row_id);
        $selected_block = self::where('block_id', '=', $block_id)->where('row_key', '=', $row_key)->where('version', '<=', $version)->orderBy('version', 'desc')->first();
        if (!empty($selected_block)) {
            return $selected_block->content;
        } else {
            return null;
        }
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
        $repeater_data = BlockManager::get_data_for_version(new self, $version, array('row_key'), array($row_keys));
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
                $repeater_blocks = BlockManager::get_data_for_version(new PageBlock, $version, array('block_id', 'page_id'), array($repeater_block_ids, $page_id));
            } else {
                $repeater_blocks = BlockManager::get_data_for_version(new PageBlockDefault, $version, array('block_id'), array($repeater_block_ids));
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
        $repeaters_data = BlockManager::get_data_for_version(new self, $version, array('row_key'), array($row_keys));
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
            $repeaterRowsData = BlockManager::get_data_for_version(new static, $version, ['row_key'], [array_keys($rowKeyToId)]) ?: [];
            foreach ($repeaterRowsData as $repeaterRowData) {
                $repeaterRowId = $rowKeyToId[$repeaterRowData->row_key];
                if (!isset($rowsByRowKey[$repeaterRowId])) {
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
