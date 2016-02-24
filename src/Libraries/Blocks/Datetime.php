<?php namespace CoasterCms\Libraries\Blocks;

use DateTime as Dt;

class Datetime extends _Base
{

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($options['format'])) {
            $block_data = date($options['format'], strtotime($block_data));
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
            $date = Dt::createFromFormat("d/m/Y H:i", $jquery_dt);
            if (!empty($date)) {
                return $date->format("Y-m-d H:i:s");
            }
        }
        return null;
    }

    public static function mysqlToJQuery($mysql_dt)
    {
        if (!empty($mysql_dt) && strtotime($mysql_dt)) {
            return date("d/m/Y H:i", strtotime($mysql_dt));
        }
        return '';
    }

}