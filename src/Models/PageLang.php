<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class PageLang extends Eloquent
{
    use DataPreLoad {DataPreLoad::preload as traitPreload;}

    /**
     * @var string
     */
    protected $table = 'page_lang';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function page()
    {
        return $this->hasOne('CoasterCms\Models\Page', 'id');
    }

    /**
     * @param string|int $key
     * @param bool $force
     * @return static
     */
    public static function preload($key, $force = false)
    {
        if (!static::_preloadIsset() || $force) {
            $languages = [Language::current()];
            if (config('coaster::frontend.language_fallback') == 1 &&  !in_array(config('coaster::frontend.language'), $languages)) {
                $languages[] = config('coaster::frontend.language');
            }
            foreach ($languages as $language) {
                $data = self::where('language_id', '=', $language)->get();
                static::_preload($data);
            }
        }
        return static::traitPreload($key);
    }

    protected static function _preloadByColumn()
    {
        return ['page_id'];
    }

    /**
     * @param $pageId
     * @return string
     */
    public static function getUrl($pageId)
    {
        return static::preload($pageId)->url;
    }

    /**
     * @param $pageId
     * @return string
     */
    public static function getName($pageId)
    {
        return static::preload($pageId)->name;
    }

}