<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PageSearchLog extends Eloquent
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
    
    /**
     * Check for search data
     * @return bool
     */
    public static function hasSearchData()
    {
      return self::count() > 0;
    }

}
