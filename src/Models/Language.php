<?php namespace CoasterCms\Models;

use Illuminate\Support\Facades\Session;

class Language extends _BaseEloquent
{

    protected $table = 'languages';

    public static function set($value)
    {
        Session::put('language', $value);
    }

    public static function current()
    {
        $language = Session::get('language');
        if (empty($language)) {
            $language = config('coaster::frontend.language');
            self::set($language);
        }
        return $language;
    }

    public static function selectArray()
    {
        $array = [];
        foreach (self::all() as $language) {
            $array[$language->id] = $language->language;
        }
        return $array;
    }

}