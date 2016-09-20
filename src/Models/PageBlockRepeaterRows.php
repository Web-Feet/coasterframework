<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class PageBlockRepeaterRows extends Eloquent
{
    use DataPreLoad;

    protected $table = 'page_blocks_repeater_rows';

    protected static function preloadRepeater($repeaterId)
    {
        $key = 'repeater' . $repeaterId;
        if (!static::_preloadIsset($key)) {
            $data = static::where('repeater_id', '=', $repeaterId)->get();
            static::_preloadOnce($data, $key, ['row_id']);
        }
        return static::_preloadGetArray($key);
    }

    public static function add_row_key($repeater_id, $row_id)
    {
        $repeaterRows = static::preloadRepeater($repeater_id);
        if (!isset($repeaterRows[$row_id])) {
            $repeater_row = new PageBlockRepeaterRows;
            $repeater_row->repeater_id = $repeater_id;
            $repeater_row->row_id = $row_id;
            $repeater_row->save();
            $repeaterRows[$row_id] = $repeater_row;
            static::_preloadAdd('repeater' . $repeater_id, $row_id, $repeater_row);
        }
        return $repeaterRows[$row_id];
    }

    public static function get_row_key($repeater_id, $row_id)
    {
        $repeaterRows = static::preloadRepeater($repeater_id);
        return empty($repeaterRows[$row_id]) ? 0 : $repeaterRows[$row_id]->id;
    }

    public static function get_row_objects($repeater_id, $randomLimit = false)
    {
        $repeaterRows = static::preloadRepeater($repeater_id);
        if ($randomLimit !== false) {
            shuffle($repeaterRows);
            return array_slice($repeaterRows, 0, $randomLimit);
        }
        return $repeaterRows;
    }

}