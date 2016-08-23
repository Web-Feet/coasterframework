<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;

class Datetime extends _Base
{

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($options['format'])) {
            if (strpos($options['format'], 'coaster.') === 0) {
                $options['format'] = config('coaster:date.format'.substr($options['format'], 8));
            }
            $block_data = (new Carbon($block_data))->format($options['format']);
        }
        return $block_data;
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        self::$edit_id = array($block->id);
        return self::mysqlToJQuery($block_data);
    }

    public static function save($block_content)
    {
        return self::jQueryToMysql($block_content);
    }

    public static function jQueryToMysql($jquery_dt)
    {
        if (!empty($jquery_dt)) {
            $date = Carbon::createFromFormat(config('coaster::date.format.jq_php'), $jquery_dt);
            if (!empty($date)) {
                return $date->format("Y-m-d H:i:s");
            }
        }
        return '';
    }

    public static function mysqlToJQuery($mysql_dt)
    {
        if (!empty($mysql_dt) && strtotime($mysql_dt)) {
            return (new Carbon($mysql_dt))->format(config('coaster::date.format.jq_php'));
        }
        return '';
    }

}