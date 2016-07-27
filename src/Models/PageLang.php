<?php namespace CoasterCms\Models;

use Eloquent;

class PageLang extends Eloquent
{

    /**
     * @var string
     */
    protected $table = 'page_lang';

    /**
     * @var array
     */
    protected static $_preLoadedPageLang = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function page()
    {
        return $this->hasOne('CoasterCms\Models\Page', 'id');
    }

    /**
     * @param PageLang $obj
     */
    public static function restore(self $obj)
    {
        $obj->save();
    }

    /**
     * @param int $page_id
     * @return PageLang
     */
    public static function preload($page_id)
    {
        if (empty(self::$_preLoadedPageLang)) {
            $pageLangs = self::where('language_id', '=', Language::current())->get();
            foreach ($pageLangs as $pageLang) {
                self::$_preLoadedPageLang[$pageLang->page_id] = $pageLang;
            }
            // fill in blanks with default language
            if (config('coaster::frontend.language_fallback') == 1 && Language::current() != config('coaster::frontend.language')) {
                $pageDefaultLangs = self::where('language_id', '=', config('coaster::frontend.language'))->get();
                foreach ($pageDefaultLangs as $pageDefaultLang) {
                    if (empty(self::$_preLoadedPageLang[$pageDefaultLang->page_id])) {
                        self::$_preLoadedPageLang[$pageDefaultLang->page_id] = $pageDefaultLang;
                    }
                }
            }
        }

        if (!empty(self::$_preLoadedPageLang[$page_id])) {
            return self::$_preLoadedPageLang[$page_id];
        } else {
            $null = new self;
            $null->page_id = $page_id;
            $null->language_id = Language::current();
            $null->url = 'not_set';
            $null->name = 'Not set';
            $null->live_version = 1;
            return $null;
        }
    }

    /**
     * @param $pageId
     * @return string
     */
    public static function url($pageId)
    {
        return self::preload($pageId)->url;
    }

    /**
     * @param $pageId
     * @return string
     */
    public static function name($pageId)
    {
        return self::preload($pageId)->name;
    }

}