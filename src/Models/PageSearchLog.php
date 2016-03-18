<?php namespace CoasterCms\Models;

class PageSearchLog extends _BaseEloquent
{

    protected $table = 'page_search_log';

    public static function add_term($term)
    {
        $existing = self::where('term', '=', $term)->first();
        if (!empty($existing)) {
            $existing->count++;
            $existing->save();
        } else {
            $new_term = new PageSearchLog;
            $new_term->term = $term;
            $new_term->count = 1;
            $new_term->save();
        }
    }

}