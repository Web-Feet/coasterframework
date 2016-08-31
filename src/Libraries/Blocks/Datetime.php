<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Models\PageBlock;

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

    public static function filter($block_id, $search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($block_id);
        $page_ids = array();
        if (!empty($live_blocks)) {
            $search = is_array($search) ? $search : [$search];
            $search = array_map(function($searchValue) {return is_a($searchValue, Carbon::class) ? $searchValue : new Carbon($searchValue);}, $search);
            foreach ($live_blocks as $live_block) {
                $current = (new Carbon($live_block->content));
                switch ($type) {
                    case '=':
                        if ($current->eq($search[0])) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        if ($current->gte($search[0]) && $current->lt($search[1])) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

}