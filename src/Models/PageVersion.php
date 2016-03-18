<?php namespace CoasterCms\Models;

use Illuminate\Support\Facades\Auth;

class PageVersion extends _BaseEloquent
{

    protected $table = 'page_versions';

    public function user()
    {
        return $this->belongsTo('CoasterCms\Models\User');
    }

    public static function latest_version($page_id, $return_obj = false)
    {
        $version = self::where('page_id', '=', $page_id)->orderBy('version_id', 'desc')->first();
        if (!empty($version)) {
            return $return_obj ? $version : $version->version_id;
        }
        return 0;
    }

    public static function add_new($page_id, $label = null)
    {
        $page_version = new self;
        $page_version->page_id = $page_id;
        $page_version->version_id = self::latest_version($page_id) + 1;
        $page_version->template = !empty($page_id) ? Page::find($page_id)->template : 0;
        $page_version->label = $label;
        $page_version->preview_key = base_convert((rand(10, 99) . microtime(true) * 10000), 10, 36);
        $page_version->save();
        return $page_version;
    }


    public function publish($set_live = false)
    {
        $page_lang = PageLang::where('page_id', '=', $this->page_id)->where('language_id', '=', Language::current())->first();
        $page = Page::find($this->page_id);
        if (!empty($page_lang) && !empty($page) && ((!config('coaster::admin.publishing') && Auth::action('pages.version-publish', ['page_id' => $this->page_id])) || (config('coaster::admin.publishing') && Auth::action('pages.edit', ['page_id' => $this->page_id])))) {
            $page_lang->live_version = $this->version_id;
            $page_lang->save();
            $page->template = $this->template;
            if ($set_live && $page->live == 0) {
                if (!empty($page->live_start) || !empty($page->live_end)) {
                    $page->live = 2;
                } else {
                    $page->live = 1;
                }
            }
            $page->save();
            return 1;
        }
        return 0;
    }

    public function save(array $options = array())
    {
        if (empty($options['system'])) {
            $this->user_id = Auth::user()->id;
        }
        return parent::save($options);
    }

    public static function restore($obj)
    {
        $obj->save();
    }

    public function __get($key)
    {
        if ($key == 'label') {
            return parent::__get($key) ?: date("g:i A d/m/y", strtotime($this->created_at));
        } else {
            return parent::__get($key);
        }
    }

}