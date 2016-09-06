<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;
use Session;

class Language extends Eloquent
{
    use DataPreLoad;

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
        static::_preloadOnce(null, 'idToLanguage', ['id'], 'language');
        return static::_preloadGet('idToLanguage');
    }

}