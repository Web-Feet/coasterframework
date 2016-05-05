<?php namespace CoasterCms\Models;

use Eloquent;

class PageBlockRepeaterRows extends Eloquent
{

    protected $table = 'page_blocks_repeater_rows';

    private static $preloaded_repeaters = [];

    private static function _preload($repeater_id)
    {
        if (!isset(self::$preloaded_repeaters[$repeater_id])) {
            self::$preloaded_repeaters[$repeater_id] = [];
            $repeater_rows = self::where('repeater_id', '=', $repeater_id)->get();
            foreach ($repeater_rows as $repeater_row) {
                self::$preloaded_repeaters[$repeater_row->repeater_id][$repeater_row->row_id] = $repeater_row;
            }
        }
    }

    public static function add_row_key($repeater_id, $row_id)
    {
        self::_preload($repeater_id);
        if (!isset(self::$preloaded_repeaters[$repeater_id])) {
            self::$preloaded_repeaters[$repeater_id] = [];
        }
        if (!isset(self::$preloaded_repeaters[$repeater_id][$row_id])) {
            $repeater_row = new PageBlockRepeaterRows;
            $repeater_row->repeater_id = $repeater_id;
            $repeater_row->row_id = $row_id;
            $repeater_row->save();
            self::$preloaded_repeaters[$repeater_id][$row_id] = $repeater_row;
        }
        return self::$preloaded_repeaters[$repeater_id][$row_id];
    }

    public static function get_row_key($repeater_id, $row_id)
    {
        self::_preload($repeater_id);
        if (!empty(self::$preloaded_repeaters[$repeater_id][$row_id])) {
            return self::$preloaded_repeaters[$repeater_id][$row_id]->id;
        } else {
            return null;
        }
    }

    public static function get_row_objects($repeater_id, $randomLimit = false)
    {
        self::_preload($repeater_id, $randomLimit);
        $rows_data = [];
        if (!empty(self::$preloaded_repeaters[$repeater_id])) {
            if ($randomLimit !== false) {
                $random = self::$preloaded_repeaters[$repeater_id];
                shuffle($random);
                return array_slice($random, 0, $randomLimit);
            } else {
                return self::$preloaded_repeaters[$repeater_id];
            }
        }
        return $rows_data;
    }

    public static function restore($obj)
    {
        $obj->save();
    }

}