<?php namespace CoasterCms\Models;

use Eloquent;

class PageLang extends Eloquent
{

    protected $table = 'page_lang';
    public static $preloaded_page_lang = array();
    public static $preloaded_full_paths = array();

    public function page()
    {
        return $this->hasOne('CoasterCms\Models\Page', 'id');
    }

    public static function restore($obj)
    {
        $obj->save();
    }

    public static function preload($page_id)
    {
        if (empty(self::$preloaded_page_lang)) {
            $page_langs = self::where('language_id', '=', Language::current())->get();
            foreach ($page_langs as $page_lang) {
                self::$preloaded_page_lang[$page_lang->page_id] = $page_lang;
            }
            // fill in blanks with default language
            if (config('coaster::frontend.language_fallback') == 1 && Language::current() != config('coaster::frontend.language')) {
                $page_default_langs = self::where('language_id', '=', config('coaster::frontend.language'))->get();
                foreach ($page_default_langs as $page_default_lang) {
                    if (empty(self::$preloaded_page_lang[$page_default_lang->page_id])) {
                        self::$preloaded_page_lang[$page_default_lang->page_id] = $page_default_lang;
                    }
                }
            }
        }
        if (!empty(self::$preloaded_page_lang[$page_id])) {
            return self::$preloaded_page_lang[$page_id];
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

    public static function url($page_id)
    {
        // remove comma if exists, only need for full paths
        $string = explode(',', $page_id);
        $page_id = $string[0];
        self::preload($page_id);
        if (!empty(self::$preloaded_page_lang[$page_id])) {
            return self::$preloaded_page_lang[$page_id]->url;
        } else {
            return '';
        }
    }

    public static function name($page_id)
    {
        // remove comma if exists, only need for full paths
        $string = explode(',', $page_id);
        $page_id = $string[0];
        self::preload($page_id);
        if (!empty(self::$preloaded_page_lang[$page_id])) {
            return self::$preloaded_page_lang[$page_id]->name;
        } else {
            return '';
        }
    }

    public static function getPreloadedFullPaths()
    {
        self::_preload_full_paths();
        return self::$preloaded_full_paths;
    }

    public static function full_url($page_id)
    {
        self::_preload_full_paths();
        if (!empty(self::$preloaded_full_paths[$page_id])) {
            return self::$preloaded_full_paths[$page_id]->full_url;
        } else {
            return '';
        }
    }

    public static function full_name($page_id, $sep = ' &raquo; ')
    {
        self::_preload_full_paths();
        if (!empty(self::$preloaded_full_paths[$page_id])) {
            return str_replace('{sep}', $sep, self::$preloaded_full_paths[$page_id]->full_name);
        } else {
            return '';
        }
    }

    public static function get_full_path($page_id, $sep = ' &raquo; ')
    {
        self::_preload_full_paths();
        if (!empty(self::$preloaded_full_paths[$page_id])) {
            $page_data = self::$preloaded_full_paths[$page_id];
            $page_data->full_name = str_replace('{sep}', $sep, $page_data->full_name);
            return $page_data;
        } else {
            $default = new \stdClass;
            $default->name = 'Error: not found';
            $default->url = 'Error: not found';
            $default->full_name = 'Error: not found';
            $default->full_url = 'Error: not found';
            $default->group_default = false;
            return $default;
        }
    }

    public static function get_full_paths($page_ids, $sep = ' &raquo; ')
    {
        self::_preload_full_paths();
        $paths = array();
        foreach (self::$preloaded_full_paths as $key => $full_path) {
            if (!is_int($key)) {
                $tmp = explode(',', $key);
                $page_id = $tmp[0];
            } else {
                $page_id = $key;
            }
            if (in_array($page_id, $page_ids) && !$full_path->group_default) {
                $full_path->full_name = str_replace('{sep}', $sep, $full_path->full_name);
                $paths[$key] = $full_path;
            }
        }
        return $paths;
    }

    private static function _preload_full_paths()
    {
        if (empty(self::$preloaded_full_paths)) {
            $top_level_pages = Page::child_page_ids(0);
            self::_load_sub_paths($top_level_pages);
        }
    }

    private static function _load_sub_paths($page_ids, $parent = null)
    {
        $groups = PageGroup::all();
        $groups_array = array();
        foreach ($groups as $group) {
            $groups_array[$group->id] = $group;
        }
        foreach ($page_ids as $page_id) {
            $pl = self::preload($page_id);
            $page_data = Page::preload($page_id);
            $page_path = clone $pl;
            $page_path->group_default = false;
            if (!empty($parent)) {
                $page_path->full_name = $parent->full_name . '{sep}' . $page_path->name;
                if ($page_data->link > 0) {
                    $page_path->full_url = $page_path->url;
                } else {
                    $page_path->full_url = $parent->full_url . '/' . $page_path->url;
                }
            } else {
                if ($page_path->url != '/' && $page_data->link == 0) {
                    $page_path->url = '/' . $page_path->url;
                }
                $page_path->full_url = $page_path->url;
                $page_path->full_name = $page_path->name;
            }
            self::$preloaded_full_paths[$page_id] = $page_path;
            $child_ids = Page::child_page_ids($page_id);
            if (!empty($child_ids)) {
                self::_load_sub_paths($child_ids, $page_path);
            } else {
                if ($page_data->group_container > 0) {
                    $group = PageGroup::find($page_data->group_container);
                    if (!empty($group)) {
                        foreach ($group->itemPageIds() as $group_page) {
                            $pl = self::preload($group_page);
                            $group_page_path = clone $pl;
                            $group_page_path->full_name = $page_path->full_name . '{sep}' . $group_page_path->name;
                            $group_page_path->full_url = $page_path->url . '/' . $group_page_path->url;
                            $group_page_path->group_default = false;
                            self::$preloaded_full_paths[$group_page . ',' . $page_id] = $group_page_path;
                            if (empty(self::$preloaded_full_paths[$group_page]) || $groups_array[$page_data->group_container]->default_parent == $page_id) {
                                $def_group_page_path = clone $group_page_path;
                                $def_group_page_path->group_default = true;
                                self::$preloaded_full_paths[$group_page] = $def_group_page_path;
                            }
                        }
                    }
                }
            }
        }
    }

}