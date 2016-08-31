<?php namespace CoasterCms\Models;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use Eloquent;
use View;

class Page extends Eloquent
{

    protected $table = 'pages';
    protected static $preloaded_pages = array();
    protected static $preloaded_page_children = array();
    protected static $preloaded_catpages = array();

    public function page_blocks()
    {
        return $this->hasMany('CoasterCms\Models\PageBlock');
    }

    public function page_lang()
    {
        return $this->hasMany('CoasterCms\Models\PageLang');
    }

    public function page_default_lang()
    {
        return $this->hasOne('CoasterCms\Models\PageLang')->where('language_id', '=', config('coaster::frontend.language'));
    }

    public function groups()
    {
        return $this->belongsToMany('CoasterCms\Models\PageGroup', 'page_group_pages', 'page_id', 'group_id');
    }

    public function versions()
    {
        return $this->hasMany('CoasterCms\Models\PageVersion');
    }

    public function is_live()
    {
        if ($this->attributes['live'] == 1) {
            return true;
        } elseif ($this->attributes['live_start'] || $this->attributes['live_end']) {
            $live_from = strtotime($this->attributes['live_start']) ?: time() - 10;
            $live_to = strtotime($this->attributes['live_end']) ?: time() + 10;
            if ($live_from < time() && $live_to > time()) {
                return true;
            }
        }
        return false;
    }

    public function groupItemsNames()
    {
        $itemNames = [];
        foreach ($this->groups as $group) {
            $itemNames[] = $group->item_name;
        }
        return implode('/', array_unique($itemNames));
    }

    public function groupNames()
    {
        $itemNames = [];
        foreach ($this->groups as $group) {
            $itemNames[] = $group->name;
        }
        return implode('/', array_unique($itemNames));
    }

    public function groupIds()
    {
        $ids = [];
        foreach ($this->groups as $group) {
            $ids[] = $group->id;
        }
        return array_unique($ids);
    }

    public function canDuplicate()
    {
        // must be able to add to all groups and parent page of existing page
        foreach ($this->groups as $group) {
            if (!$group->canAddItems()) {
                return false;
            }
        }
        return $this->parent < 0 || Auth::action('pages.add', ['page_id' => $this->parent]);
    }

    public function parentPathIds()
    {
        $urls = [];
        if ($this->parent >= 0) {
            $urls[$this->parent] = 100;
        }
        foreach ($this->groups as $group) {
            foreach ($group->containerPagesFiltered($this->id) as $containerPage) {
                $urls[$containerPage->id] = $containerPage->group_container_url_priority ?: $group->url_priority;
            }
        }
        arsort($urls);
        if (!empty($urls[$this->canonical_parent])) {
            $urls = [$this->canonical_parent => $urls[$this->canonical_parent]] + $urls;
        }
        return $urls ?: [-1 => 100];
    }

    public static function get_total($include_group = false)
    {
        $pages = self::where('link', '=', '0');
        if (!$include_group) {
            $pages = $pages->where('parent', '>=', '0');
        }
        return $pages->count();
    }

    public static function at_limit($for_group = false)
    {
        $limit = ($for_group && config('coaster::site.groups') !== '') ? config('coaster::site.groups') : config('coaster::site.pages');
        return $limit === '0' ? false : (self::get_total($for_group) >= $limit);
    }

    public static function preload($pageId)
    {
        if (empty(self::$preloaded_pages)) {
            $pages = self::all();
            foreach ($pages as $page) {
                self::$preloaded_pages[$page->id] = $page;
            }
        }
        return !empty(self::$preloaded_pages[$pageId]) ? self::$preloaded_pages[$pageId] : new self;
    }

    // returns child page ids (parent only / no group)
    public static function getChildPageIds($pageId)
    {
        if (empty(self::$preloaded_page_children)) {
            self::preload(-1);
            foreach (self::$preloaded_pages as $key => $page) {
                if (!isset(self::$preloaded_page_children[$page->parent])) {
                    self::$preloaded_page_children[$page->parent] = [];
                }
                self::$preloaded_page_children[$page->parent][] = $page->id;
            }
        }
        return !empty(self::$preloaded_page_children[$pageId]) ? self::$preloaded_page_children[$pageId] : [];
    }

    public static function getChildPages($categoryPageId)
    {
        $categoryPagesIds = self::getChildPageIds($categoryPageId);
        return self::getOrderedPages($categoryPagesIds);
    }

    // returns ordered pages
    public static function getOrderedPages($pageIds)
    {
        self::preload(-1);
        $pages = [];
        foreach ($pageIds as $pageId) {
            $pages[$pageId] = self::$preloaded_pages[$pageId];
        }
        uasort($pages, ['self', '_orderAsc']);
        return $pages;
    }

    protected static function _orderAsc($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    public static function category_pages($page_id, $check_live = false)
    {
        $check_live_string = $check_live ? 'true' : 'false';
        // check if previously generated (used a lot in the link blocks)
        if (!empty(self::$preloaded_catpages[$page_id])) {
            if (!empty(self::$preloaded_catpages[$page_id][$check_live_string])) {
                return self::$preloaded_catpages[$page_id][$check_live_string];
            }
        } else {
            self::$preloaded_catpages[$page_id] = array();
        }
        $pages = [];
        $page = self::preload($page_id);
        if ($page->exists && $page->group_container > 0) {
            $group = PageGroup::find($page->group_container);
            if (!empty($group)) {
                $group_pages = $group->itemPageIdsFiltered($page_id, $check_live, true);
                foreach ($group_pages as $group_page) {
                    $pages[] = self::preload($group_page);
                }
            }
        } else {
            $pages = self::getChildPages($page_id);
            if ($check_live) {
                foreach ($pages as $key => $page) {
                    if (!$page->is_live()) {
                        unset($pages[$key]);
                    }
                }
            }
        }
        self::$preloaded_catpages[$page_id][$check_live_string] = $pages;
        return $pages;
    }

    public static function get_page_list($options = array())
    {
        $default_options = array('links' => true, 'group_pages' => true, 'language_id' => Language::current(), 'parent' => null);
        $options = array_merge($default_options, $options);

        if ($parent = !empty($options['parent']) ? self::find($options['parent']) : null) {
            if ($parent->group_container > 0) {
                $group = PageGroup::find($parent->group_container);
                $pages = $group->itemPagesFiltered($parent->id);
            } else {
                $pages = self::where('parent', '=', $options['parent'])->get();
            }
        } else {
            $pages = self::all();
        }

        $pages_array = array();
        $max_link = $options['links'] ? 1 : 0;
        $min_parent = $options['group_pages'] ? -1 : 0;
        foreach ($pages as $page) {
            if (config('coaster::admin.advanced_permissions') && !Auth::action('pages', ['page_id' => $page->id])) {
                continue;
            }
            if ($page->link <= $max_link && $page->parent >= $min_parent) {
                $pages_array[] = $page->id;
            }
        }

        $paths = $options['group_pages'] ? Path::getFullPathsVariations($pages_array) : Path::getFullPaths($pages_array);
        $list = array();
        foreach ($paths as $page_id => $path) {
            if ((!isset($options['exclude_home']) || $path->fullUrl != '/') && !is_null($path->fullUrl)) {
                $list[$page_id] = $path->fullName;
            }
        }

        // order
        asort($list);
        return $list;
    }

    public static function get_page_list_view($parent, $level, $cat_url = '')
    {
        if ($childPages = self::getChildPages($parent)) {
            $pages_li = '';
            foreach ($childPages as $child_page) {

                if (config('coaster::admin.advanced_permissions') && !Auth::action('pages', ['page_id' => $child_page->id])) {
                    continue;
                }

                $permissions = [];
                $permissions['add'] = Auth::action('pages.add', ['page_id' => $child_page->id]);
                $permissions['edit'] = Auth::action('pages.edit', ['page_id' => $child_page->id]);
                $permissions['delete'] = Auth::action('pages.delete', ['page_id' => $child_page->id]);
                $permissions['group'] = Auth::action('groups.pages', ['page_id' => $child_page->id]);
                $permissions['galleries'] = Auth::action('gallery.edit', ['page_id' => $child_page->id]);
                $permissions['forms'] = Auth::action('forms.submissions', ['page_id' => $child_page->id]);
                $permissions['blog'] = Auth::action('system.wp_login');

                $page_lang = PageLang::preload($child_page->id);

                $li_info = new \stdClass;
                $li_info->preview_link = $cat_url . '/' . $page_lang->url;
                $li_info->preview_link = ($li_info->preview_link == '//') ? '/' : $li_info->preview_link;
                $li_info->number_of_forms = Template::preload_blocks_of_type('form', $child_page->template);
                $li_info->number_of_galleries = Template::preload_blocks_of_type('gallery', $child_page->template);

                if (trim($page_lang->url, '/') == '' && $child_page->parent == 0 && $child_page->link == 0) {
                    $permissions['add'] = false;
                }
                if ($child_page->group_container > 0) {
                    $li_info->type = 'type_group';
                    $li_info->leaf = '';
                    $li_info->group = PageGroup::find($child_page->group_container);
                } else {
                    if ($child_page->link == 1) {
                        $li_info->preview_link = $page_lang->url;
                        $li_info->type = 'type_link';
                    } else {
                        $li_info->type = 'type_normal';
                    }
                    $li_info->group = '';
                }
                $li_info->leaf = self::get_page_list_view($child_page->id, $level + 1, $li_info->preview_link);
                if (trim($li_info->preview_link, '/') != '' && trim($li_info->preview_link, '/') == trim(config('coaster::blog.url'), '/')) {
                    $li_info->blog = route('coaster.admin.system.wp-login');
                } else {
                    $li_info->blog = '';
                }
                if (!$child_page->is_live()) {
                    $li_info->type = 'type_hidden';
                    if ($child_page->link == 0) {
                        if ($liveVersion = PageVersion::get_live_version($child_page->id)) {
                            $li_info->preview_link .= '?preview=' . $liveVersion->preview_key;
                        }
                    }
                }
                $pages_li .= View::make('coaster::partials.pages.li', array('page' => $child_page, 'page_lang' => $page_lang, 'li_info' => $li_info, 'permissions' => $permissions))->render();
            }
            return View::make('coaster::partials.pages.ol', array('pages_li' => $pages_li, 'level' => $level))->render();
        }
        return null;
    }

    public function delete()
    {
        $page_name = PageLang::preload($this->id)->name;
        $log_id = AdminLog::new_log('Page \'' . $page_name . '\' deleted (Page ID ' . $this->id . ')');

        // make backups
        $page_versions = PageVersion::where('page_id', '=', $this->id);
        $page_langs = PageLang::where('page_id', '=', $this->id);
        $page_blocks = PageBlock::where('page_id', '=', $this->id);
        $menu_items = MenuItem::where('page_id', '=', $this->id)->orWhere('page_id', 'LIKE', $this->id . ',%');
        $user_role_page_actions = UserRolePageAction::where('page_id', '=', $this->id);
        $page_groups = PageGroupPage::where('page_id', '=', $this->id);

        $publish_request_ids = [];
        foreach ($page_versions as $page_version) {
            $publish_request_ids[] = $page_version->id;
        }

        Backup::new_backup($log_id, '\CoasterCms\Models\Page', $this);
        Backup::new_backup($log_id, '\CoasterCms\Models\PageVersion', $page_versions->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\PageLang', $page_langs->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\PageBlock', $page_blocks->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\MenuItem', $menu_items->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\UserRolePageAction', $user_role_page_actions->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\PageGroupPages', $page_groups->get());

        // publish requests
        if (!empty($publish_request_ids)) {
            $page_publish_requests = PagePublishRequests::where('page_version_id', '=', $publish_request_ids);
            Backup::new_backup($log_id, '\CoasterCms\Models\PagePublishRequests', $page_publish_requests->get());
            $page_publish_requests->delete();
        }

        // repeater data
        $repeater_block_ids = Block::get_repeater_blocks();
        if (!empty($repeater_block_ids)) {
            $repeater_blocks = PageBlock::whereIn('block_id', $repeater_block_ids)->where('page_id', $this->id)->get();
            if (!$repeater_blocks->isEmpty()) {
                $repeater_ids = [];
                foreach ($repeater_blocks as $repeater_block) {
                    $repeater_ids[] = $repeater_block->content;
                }
                $repeater_row_keys = PageBlockRepeaterRows::whereIn('repeater_id', $repeater_ids);
                $repeater_row_keys_get = $repeater_row_keys->get();
                if (!$repeater_row_keys_get->isEmpty()) {
                    $row_keys = [];
                    foreach ($repeater_row_keys_get as $repeater_row_key) {
                        $row_keys[] = $repeater_row_key->id;
                    }
                    $repeater_data = PageBlockRepeaterData::whereIn('row_key', $row_keys);
                    Backup::new_backup($log_id, '\CoasterCms\Models\PageBlockRepeaterRows', $repeater_row_keys->get());
                    Backup::new_backup($log_id, '\CoasterCms\Models\PageBlockRepeaterData', $repeater_data->get());
                    $repeater_data->delete();
                    $repeater_row_keys->delete();
                }
            }
        }

        // delete data
        $this->groups()->detach();
        $page_versions->delete();
        $page_langs->delete();
        $page_blocks->delete();
        $menu_items->delete();
        $user_role_page_actions->delete();
        PageSearchData::where('page_id', '=', $this->id)->delete();
        parent::delete();

        $return_log_ids = array($log_id);

        $child_pages = self::where('parent', '=', $this->id)->get();
        if (!empty($child_pages)) {
            foreach ($child_pages as $child_page) {
                $log_ids = $child_page->delete();
                $return_log_ids = array_merge($log_ids, $return_log_ids);
            }
        }

        sort($return_log_ids);
        return $return_log_ids;
    }

    public static function restore($obj)
    {
        $obj->save();
    }

}
