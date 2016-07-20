<?php namespace CoasterCms\Models;

use Auth;
use Eloquent;

class Page extends Eloquent
{

    protected $table = 'pages';
    private static $preloaded_pages = array();
    private static $preloaded_page_children = array();
    private static $preloaded_catpages = array();

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

    public function group()
    {
        return $this->belongsTo('CoasterCms\Models\PageGroup', 'in_group');
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

    public static function get_total($include_group = false)
    {
        if ($include_group) {
            return self::where('link', '=', '0')->count();
        } else {
            return self::where('link', '=', '0')->where('in_group', '=', '0')->where('group_container', '=', '0')->count();
        }
    }

    public static function at_limit()
    {
        if (self::get_total() >= config('coaster::site.pages') && config('coaster::site.pages') != 0) {
            return true;
        }
        return false;
    }

    public static function preload($page_id)
    {
        $pageIdParts = explode(',', $page_id);
        $page_id = $pageIdParts[0];
        if (empty(self::$preloaded_pages)) {
            $pages = self::all();
            foreach ($pages as $page) {
                self::$preloaded_pages[$page->id] = $page;
            }
        }
        if (!empty(self::$preloaded_pages[$page_id])) {
            return self::$preloaded_pages[$page_id];
        } else {
            return null;
        }
    }

    // returns page ids
    public static function child_page_ids($page_id)
    {
        if (empty(self::$preloaded_page_children)) {
            self::preload(-1);
            foreach (self::$preloaded_pages as $key => $page) {
                if (!isset(self::$preloaded_page_children[$page->parent])) {
                    self::$preloaded_page_children[$page->parent] = array();
                }
                self::$preloaded_page_children[$page->parent][] = $page->id;
            }
        }
        if (!empty(self::$preloaded_page_children[$page_id])) {
            return self::$preloaded_page_children[$page_id];
        } else {
            return [];
        }
    }

    // returns ordered pages
    public static function get_ordered_pages($page_ids)
    {
        self::preload(-1);
        $pages = array();
        foreach ($page_ids as $page_id) {
            $pages[] = self::$preloaded_pages[$page_id];
        }
        usort($pages, array("self", "_order_asc"));
        return $pages;
    }

    private static function _order_asc($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    public static function category_pages($page_id, $check_live = false)
    {
        $check_live_string = ($check_live) ? 'true' : 'false';
        // check if previously generated (used a lot in the link blocks)
        if (!empty(self::$preloaded_catpages[$page_id])) {
            if (!empty(self::$preloaded_catpages[$page_id][$check_live_string])) {
                return self::$preloaded_catpages[$page_id][$check_live_string];
            }
        } else {
            self::$preloaded_catpages[$page_id] = array();
        }
        $page = Page::preload($page_id);
        if (!empty($page) && $page->group_container > 0) {
            $group_id = $page->group_container;
            $group = PageGroup::find($group_id);
            $page_lang = PageLang::preload($page_id);
            $pages = array();
            if (!empty($group)) {
                $filters = PageGroupAttribute::where('group_id', '=', $group_id)->where('filter_by_block_id', '>', 0)->get();
                $group_pages =  $group->itemPageIds($check_live, true);
                if (!empty($group_pages)) {
                    foreach ($filters as $filter) {
                        $filtered_pages = array();
                        // get data to filter by
                        $filter_by = PageBlock::preload_block($page_id, $filter->filter_by_block_id, $page_lang->live_version);
                        $filter_by = $filter_by[Language::current()];
                        if (!empty($filter_by)) {
                            $filter_by_block = Block::preload($filter->filter_by_block_id);
                            if ($filter_by_block->type == 'selectmultiple') {
                                $filter_by_content = unserialize($filter_by->content);
                            } else {
                                $filter_by_content = $filter_by->content;
                            }
                            if (empty($filter_by_content) || $filter_by_content == false) {
                                $filter_by_content = array();
                            } elseif (!is_array($filter_by_content)) {
                                $filter_by_content = array($filter_by->content);
                            }
                        } else {
                            $filter_by_content = array();
                        }
                        // get pages that match filter data
                        $page_blocks = PageBlock::whereIn('page_id', $group_pages)->where('block_id', '=', $filter->item_block_id)->get();
                        $item_block = Block::preload($filter->item_block_id);
                        foreach ($page_blocks as $page_block) {
                            if ($item_block->type == 'selectmultiple') {
                                $page_block_content = unserialize($page_block->content);
                            } else {
                                $page_block_content = $page_block->content;
                            }
                            if (empty($page_block_content) || $page_block_content == false) {
                                $page_block_content = array();
                            } elseif (!is_array($page_block_content)) {
                                $page_block_content = array($page_block->content);
                            }
                            $check = array_intersect($filter_by_content, $page_block_content);
                            if (!empty($check)) {
                                $filtered_pages[] = $page_block->page_id;
                            }
                        }
                        // remove pages from ordered array that didn't come back through the filter
                        foreach ($group_pages as $k => $group_page) {
                            if (!in_array($group_page, $filtered_pages)) {
                                unset($group_pages[$k]);
                            }
                        }
                    }
                    foreach ($group_pages as $group_page) {
                        $pages[] = Page::preload($group_page);
                    }
                }
            }
        } else {
            $pages = Page::where('parent', '=', $page_id)->orderBy('order', 'asc')->get();
            $pages = $pages->isEmpty() ? [] : $pages;
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
        $default_options = array('links' => true, 'group_pages' => true, 'language_id' => Language::current());
        $options = array_merge($default_options, $options);
        if ($options['links']) {
            $max_link = 1;
        } else {
            $max_link = 0;
        }
        if ($options['group_pages']) {
            $min_parent = -1;
        } else {
            $min_parent = 0;
        }
        $options['parent'] = !empty($options['parent']) ? (int)$options['parent'] : 0;
        if (!empty($options['parent'])) {
            $parent = self::find($options['parent']);
            if (!empty($parent)) {
                if ($parent->group_container > 0) {
                    $match_group = $parent->group_container;
                } else {
                    $match_parent = $options['parent'];
                }
            }
        }
        $pages_array = array();
        foreach (self::all() as $page) {
            if (config('coaster::admin.advanced_permissions') && !Auth::action('pages', ['page_id' => $page->id])) {
                continue;
            }
            if ($page->link <= $max_link && $page->parent >= $min_parent) {
                if (((isset($match_parent) && $page->parent == $match_parent) || !isset($match_parent)) && ((isset($match_group) && $page->in_group == $match_group) || !isset($match_group))) {
                    $pages_array[] = $page->id;
                }
            }
        }
        $paths = PageLang::get_full_paths($pages_array);
        $list = array();
        foreach ($paths as $page_id => $path) {
            if (!isset($options['exclude_home']) || $path->full_url != '/') {
                $list[$page_id] = $path->full_name;
            }
        }
        // order
        asort($list);
        return $list;
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
        parent::delete();
        $page_versions->delete();
        $page_langs->delete();
        $page_blocks->delete();
        $menu_items->delete();
        $user_role_page_actions->delete();
        PageSearchData::where('page_id', '=', $this->id)->delete();

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
