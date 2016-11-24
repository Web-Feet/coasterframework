<?php namespace CoasterCms\Models;

use Auth;
use CoasterCms\Helpers\Cms\DateTimeHelper;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use View;

class Page extends Eloquent
{
    use DataPreLoad;

    protected $table = 'pages';
    protected static $preloaded_page_children = array();
    protected static $preloaded_catpages = array();

    public function page_blocks()
    {
        return $this->hasMany('CoasterCms\Models\PageBlock');
    }

    public function page_langs()
    {
        return $this->hasMany('CoasterCms\Models\PageLang');
    }

    /**
     * @return Eloquent|null
     */
    public function pageLang()
    {
        return $this->pageCurrentLang ?: (config('coaster::frontend.language_fallback') ? $this->pageDefaultLang : null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pageCurrentLang()
    {
        return $this->hasOne('CoasterCms\Models\PageLang')->where('language_id', '=', Language::current());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pageDefaultLang()
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
        if ($this->live == 1) {
            return true;
        } elseif ($this->live_start || $this->live_end) {
            $live_from = strtotime($this->live_start) ?: time() - 10;
            $live_to = strtotime($this->live_end) ?: time() + 10;
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
        return PageGroupPage::getGroupIds($this->id);
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

    // returns child page ids (parent only / no group)
    public static function getChildPageIds($pageId)
    {
        if (empty(self::$preloaded_page_children)) {
            foreach (static::preloadArray() as $key => $page) {
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
        $pages = [];
        foreach ($pageIds as $pageId) {
            $pages[$pageId] = static::preload($pageId);
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
            $group = PageGroup::preload($page->group_container);
            if ($group->exists) {
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
                $group = PageGroup::preload($parent->group_container);
                $pages = $group->itemPageFiltered($parent->id);
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


    public static function getPageTreeView($parent)
    {
        $childPages = self::getChildPages($parent);
        return static::getPageListView($childPages, true);
    }

    public static function getPageListView($listPages, $tree = false, $level = 1, $cat_url = '')
    {
        $listPages = is_array($listPages) ? collect($listPages) : $listPages;
        if (!$listPages->isEmpty()) {
            $pages_li = '';
            foreach ($listPages as $page) {

                if (config('coaster::admin.advanced_permissions') && !Auth::action('pages', ['page_id' => $page->id])) {
                    continue;
                }

                $permissions = [];
                $permissions['add'] = Auth::action('pages.add', ['page_id' => $page->id]) && $page->parent != -1;
                $permissions['edit'] = Auth::action('pages.edit', ['page_id' => $page->id]);
                $permissions['delete'] = Auth::action('pages.delete', ['page_id' => $page->id]);
                $permissions['group'] = Auth::action('groups.pages', ['page_id' => $page->id]);
                $permissions['galleries'] = Auth::action('gallery.edit', ['page_id' => $page->id]);
                $permissions['forms'] = Auth::action('forms.submissions', ['page_id' => $page->id]);
                $permissions['blog'] = Auth::action('system.wp_login');

                $page_lang = PageLang::preload($page->id);

                $li_info = new \stdClass;
                $li_info->leaf = '';
                $li_info->altName = '';
                if ($tree) {
                    $li_info->preview_link = $cat_url . '/' . $page_lang->url;
                    $li_info->preview_link = ($li_info->preview_link == '//') ? '/' : $li_info->preview_link;
                    $childPages = self::getChildPages($page->id);
                    $li_info->leaf = self::getPageListView($childPages, true, $level + 1, $li_info->preview_link);
                } else {
                    $li_info->preview_link = Path::getFullUrl($page->id);
                    $li_info->altName = Path::getFullName($page->id);
                }
                $li_info->number_of_forms = Template::preload_blocks_of_type('form', $page->template);
                $li_info->number_of_galleries = Template::preload_blocks_of_type('gallery', $page->template);

                if (trim($page_lang->url, '/') == '' && $page->parent == 0 && $page->link == 0) {
                    $permissions['add'] = false;
                }
                if ($page->group_container > 0) {
                    $li_info->type = 'type_group';
                    $li_info->group = PageGroup::preload($page->group_container);
                    $li_info->group = $li_info->group->exists ? $li_info->group : '';
                } else {
                    if ($page->link == 1) {
                        $li_info->preview_link = $page_lang->url;
                        $li_info->type = 'type_link';
                    } else {
                        $li_info->type = 'type_normal';
                    }
                    $li_info->group = '';
                }
                if (trim($li_info->preview_link, '/') != '' && trim($li_info->preview_link, '/') == trim(config('coaster::blog.url'), '/')) {
                    $li_info->blog = route('coaster.admin.system.wp-login');
                } else {
                    $li_info->blog = '';
                }
                if (!$page->is_live()) {
                    $li_info->type = 'type_hidden';
                    if ($page->link == 0) {
                        if ($liveVersion = PageVersion::getLiveVersion($page->id)) {
                            $li_info->preview_link .= '?preview=' . $liveVersion->preview_key;
                        }
                    }
                }
                $pages_li .= View::make('coaster::partials.pages.li', array('page' => $page, 'page_lang' => $page_lang, 'li_info' => $li_info, 'permissions' => $permissions))->render();
            }
            return View::make('coaster::partials.pages.ol', array('pages_li' => $pages_li, 'level' => $level))->render();
        }
        return null;
    }

    public function delete()
    {
        $page_name = PageLang::getName($this->id);
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
        Backup::new_backup($log_id, '\CoasterCms\Models\PageGroupPage', $page_groups->get());

        // publish requests
        if (!empty($publish_request_ids)) {
            $page_publish_requests = PagePublishRequests::where('page_version_id', '=', $publish_request_ids);
            Backup::new_backup($log_id, '\CoasterCms\Models\PagePublishRequests', $page_publish_requests->get());
            $page_publish_requests->delete();
        }

        // repeater data
        $repeater_block_ids = Block::getBlockIdsOfType('repeater');
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

    public static function adminSearch($q)
    {
        return static::join('page_lang', 'page_lang.page_id', '=', 'pages.id')
            ->where('page_lang.language_id', '=', Language::current())->where('link', '=', 0)
            ->where('page_lang.name', 'like', '%'.$q.'%')
            ->get(['pages.*']);
    }

    public function tabInfo()
    {
        $contents = '';

        $publishingOn = config('coaster::admin.publishing') > 0;
        $canPublish = ($publishingOn && Auth::action('pages.version-publish', ['page_id' => $this->id])) || !$publishingOn;

        // page parent (only updated for new pages)
        if (!$this->id) {
            $parentPages = [-1 => '-- None --', 0 => '-- Top Level Page --'] + static::get_page_list(['links' => false, 'exclude_home' => true, 'group_pages' => false]);
            if (!array_key_exists($this->parent, $parentPages)) {
                $this->parent = -1;
            }
        } else {
            $parentPages = null;
        }

        // beacons selection (only updated for existing pages)
        if ($this->id && Auth::action('themes.beacons-update')) {
            $beaconSelect = BlockBeacon::getDropdownOptions($this->id);
            $beaconSelect = empty($beaconSelect->options) ? null : $beaconSelect;
        } else {
            $beaconSelect = null;
        }

        // page name, url
        $pageLang = $this->id ? PageLang::where('page_id', '=', $this->id)->where('language_id', '=', Language::current())->first() : new PageLang;
        $fullUrls = [-1 => '?', 0 => '/'];
        foreach (Path::all() as $pageId => $details) {
            $fullUrls[$pageId] = rtrim($details->fullUrl, '/') . '/';
        }
        $urlPrefixes = $this->parentPathIds();
        foreach ($urlPrefixes as $pageId => $urlPrefix) {
            if (!key_exists($pageId, $fullUrls)) {
                $fullUrls[$pageId] = '?';
            }
        }
        $contents .= View::make('coaster::partials.tabs.page_info.page_info', ['page' => $this, 'page_lang' => $pageLang, 'parentPages' => $parentPages, 'beacon_select' => $beaconSelect, 'urlArray' => $fullUrls, 'urlPrefixes' => $urlPrefixes, 'publishing_on' => $publishingOn, 'can_publish' => $canPublish])->render();

        // groups
        $groups = PageGroup::all();
        if (!$groups->isEmpty() || config('coaster::site.groups') !== '') {
            $contents .= View::make('coaster::partials.tabs.page_info.groups', ['page' => $this, 'groups' => $groups])->render();
        }

        //template
        if (empty($this->template)) {
            $this->template = config('coaster::admin.default_template');
            $parentPage = static::find($this->parent);
            if ($parentPage && $parentTemplate = Template::find($parentPage->template)) {
                $this->template = $parentTemplate->child_template;
            }
        }
        $templateData = Template::find($this->template);
        $templates = Theme::get_template_list($this->template);
        $templateSelectHidden = !empty($templateData) ? $templateData->hidden : false;

        // menu selection
        $menus = Menu::all();
        if (!$menus->isEmpty() && Auth::action('menus')) {
            $in_menus = $this->id ? MenuItem::get_page_menus($this->id) : [];
            foreach ($menus as $k => $menu) {
                $menus[$k]->in_menu = in_array($menu->id, $in_menus);
            }
        } else {
            $menus = [];
        }

        $contents .= View::make('coaster::partials.tabs.page_info.display_info', ['page' => $this, 'template' => $this->template, 'templates' => $templates, 'templateSelectHidden' => $templateSelectHidden, 'menus' => $menus, 'can_publish' => $canPublish])->render();

        // live options, sitemap
        $liveOptions = [0 => 'Not Live (Hidden)', 1 => 'Live (Ignores Dates)', 2 => 'Live Between Specific Dates/Times'];
        $sitemapOptions = [0 => 'Excluded From Sitemap', 1 => 'Included in Sitemap (If Page Live)'];
        $contents .= View::make('coaster::partials.tabs.page_info.live_options', ['page' => $this, 'liveOptions' => $liveOptions, 'sitemapOptions' => $sitemapOptions, 'disabled' => !$canPublish])->render();

        return ['Page Info', $contents];
    }

    public function tabRequests()
    {
        $header = '';
        $contents = '';
        $allRequests = PagePublishRequests::all_requests($this->id);
        if (!$allRequests->isEmpty()) {
            $awaitingRequests = PagePublishRequests::all_requests($this->id, ['status' => 'awaiting']);
            $header = 'Publish Requests';
            if ($count = $awaitingRequests->count()) {
                $header .= ' &nbsp; <span class="badge">'.$count.'</span>';
            }
            if ($awaitingRequests->isEmpty()) {
                $awaitingRequests = 'No awaiting requests';
            }

            $requests_table = View::make('coaster::partials.tabs.publish_requests.table', ['show' => ['page' => false, 'status' => true, 'requested_by' => true], 'requests' => $awaitingRequests])->render();
            $contents = View::make('coaster::partials.tabs.publish_requests.main', ['requests_table' => $requests_table]);
        }
        return [$header, $contents];
    }

    /**
     * Saves page data
     * @param PageVersion $pageVersion
     * @param array $pagePost
     * @param array $pageLangPost
     * @param array $pageGroupsPost
     * @param array $pageInfoOther
     * @return bool
     */
    public function savePostData($pageVersion, $pagePost, $pageLangPost, $pageGroupsPost, $pageInfoOther = [])
    {
        /*
         * Post data fixes
         */
        foreach ($pagePost as $k => $pagePostField) {
            if (is_array($pagePostField) && array_key_exists('exists', $pagePostField)) {
                $pagePost[$k] = array_key_exists('select', $pagePostField) ? $pagePostField['select'] : 0;
            }
        }
        if (array_key_exists('live_start', $pagePost)) {
            $pagePost['live_start'] = DateTimeHelper::jQueryToMysql($pagePost['live_start']) ?: null;
        }
        if (array_key_exists('live_end', $pagePost)) {
            $pagePost['live_end'] = DateTimeHelper::jQueryToMysql($pagePost['live_end']) ?: null;
        }
        foreach ($pageInfoOther as $k => $pageInfoOtherField) {
            if (is_array($pageInfoOtherField) && array_key_exists('exists', $pageInfoOtherField) && array_key_exists('select', $pageInfoOtherField)) {
                $pageInfoOther[$k] = $pageInfoOtherField['select'];
            }
        }

        /*
         * Overwrite default/existing data with posted data
         */
        $pageDefaults = array_merge([
            'template' => 0,
            'parent' => 0,
            'child_template' => 0,
            'order' => 0,
            'group_container' => 0,
            'group_container_url_priority' => 0,
            'canonical_parent' => 0,
            'link' => 0,
            'live' => 0,
            'sitemap' => 1,
            'live_start'=> null,
            'live_end' => null
        ], $this->getAttributes());
        foreach ($pageDefaults as $pageAttribute => $pageDefault) {
            $this->$pageAttribute = $pageDefault;
            switch ($pageAttribute) {
                case 'template': $pageVersion->$pageAttribute = $pagePost[$pageAttribute]; break;
                default:
                    $this->$pageAttribute = array_key_exists($pageAttribute, $pagePost) ? $pagePost[$pageAttribute] : $this->$pageAttribute;
            }
        }

        if (!$this->pageCurrentLang) {
            $this->setRelation('pageCurrentLang', ($d = $this->pageDefaultLang) ? $d->replicate() : new PageLang);
            unset($this->pageCurrentLang->language_id);
        }
        $pageLang = $this->pageLang();
        $pageLangDefaults = array_merge([
            'language_id' => Language::current(),
            'url' => '',
            'name' => '',
            'live_version' => 1
        ], $pageLang->getAttributes());
        foreach ($pageLangDefaults as $pageLangAttribute => $pageLangDefault) {
            $pageLang->$pageLangAttribute = array_key_exists($pageLangAttribute, $pageLangPost) ? $pageLangPost[$pageLangAttribute] : $pageLangDefault;
        }

        /*
         * Check page parent exists if set and page limit is not hit
         */
        $parent = static::find($this->parent);
        if ($this->parent > 0 && !$parent) {
            return false;
        }
        if (!$this->id && !$this->link && static::at_limit($this->parent == -1)) {
            return false;
        }

        /*
         * Check page name/url set and does not conflict
         */
        $pageLang->url = trim($pageLang->url);
        if (!$this->link) {
            $pageLang->url = strtolower(str_replace(['/', ' '], '-', $pageLang->url));
            if (preg_match('#^[-]+$#', $pageLang->url)) {
                $pageLang->url = '';
            }
            if ($pageLang->url == '' && !$this->parent) {
                $pageLang->url = '/';
            }
            $siblings = [];
            foreach ($pageGroupsPost as $pageGroupId => $checkedVal) {
                $pageGroup = PageGroup::preload($pageGroupId);
                $siblings = array_merge($pageGroup->exists ? $pageGroup->itemPageIds() : [], $siblings);
            }
            if ($this->parent >= 0) {
                $siblings = array_merge(static::getChildPageIds($this->parent), $siblings);
            }
            $siblings = array_unique($siblings);
        }
        if (!$pageLang->name) {
            FormMessage::add('page_info_lang[name]', 'page name required');
        }
        if (!$pageLang->url) {
            FormMessage::add('page_info_lang[url]', 'page url required');
        }
        if (!empty($siblings)) {
            $same_level = PageLang::where('url', '=', $pageLang->url)->whereIn('page_id', $siblings);
            $same_level = $this->id ? $same_level->where('page_id', '!=', $this->id) : $same_level;
            if (!$same_level->get()->isEmpty()) {
                FormMessage::add('page_info_lang[url]', 'url in use by another page!');
                $pageLang->url = '';
            }
        }
        if (!$pageLang->name || !$pageLang->url) {
            return false;
        }

        /*
         * If adding a page as a group container, create container / check exists
         */
        if ($this->group_container == -1) {
            $groupContainer = new PageGroup;
            $groupContainer->name = $pageLang->name;
            $groupContainer->item_name = 'Page';
            $groupContainer->default_template = 0;
            $groupContainer->save();
            $this->group_container = $groupContainer->id;
        } elseif ($this->group_container) {
            $groupContainer = PageGroup::preload($this->group_container);
            if (!$groupContainer->exists || ($pageDefaults['group_container'] != $this->group_container && !$groupContainer->canEditItems())) {
                $this->group_container = 0;
            }
        }

        /*
         * Check if page info can be updated (based on publishing auth action, or allowed if new page)
         */
        $authPageIdCheck = $this->id ?: ($this->parent > 0 ? $this->parent : 0);
        $canPublish = (config('coaster::admin.publishing') > 0 && Auth::action('pages.version-publish', ['page_id' => $authPageIdCheck])) || (config('coaster::admin.publishing') == 0 && Auth::action('pages.edit', ['page_id' => $authPageIdCheck]));
        $canPublish = $canPublish || (isset($groupContainer) && ((config('coaster::admin.publishing') > 0 && $groupContainer->canPublishItems()) || (config('coaster::admin.publishing') == 0 && $groupContainer->canEditItems())));
        $willPublish = !$this->id || $canPublish;

        /*
         * Check and save page changes
         */
        if ($willPublish) {
            // if new page set as last ordered page
            if ($this->parent >= 0 && !$this->id) {
                $lastSibling = static::where('parent', '=', $this->parent)->orderBy('order', 'desc')->first();
                $this->order = $lastSibling ? $lastSibling->order + 1 : 1;
            }

            // if new page publish template
            $this->template = $this->id ? $this->template: $pageVersion->template;
            // if link remove live template
            $this->template = $this->link ? 0 : $this->template;

            // set page live between but no dates set set as hidden, or if can't publish set as hidden
            $this->live = ($this->live == 2 && is_null($this->live_end) && is_null($this->live_start)) ? 0 : $this->live;
            $this->live = $canPublish ? $this->live : 0;

            // save page data
            $this->save();
            $pageLang->page_id = $this->id;
            $pageLang->save();
        }
        $pageVersion->page_id = $this->id;
        $pageVersion->save();

        /*
         * Update title block to the page name is new page
         */
        if (!$this->id && $titleBlock = Block::where('name', '=', config('coaster::admin.title_block'))->first()) {
            $titleBlock->setVersionId($pageVersion->version_id)->setPageId($this->id)->getTypeObject()->save($pageLang->name);
            PageSearchData::updateText(strip_tags($pageLang->name), 0, $this->id);
        }

        /*
         * Save Page Groups
         */
        $currentGroupIds = $this->groupIds();
        $newGroupIds = array_keys($pageGroupsPost);
        PageGroupPage::where('page_id', '=', $this->id)->whereIn('group_id', array_diff($currentGroupIds, $newGroupIds))->delete();
        foreach (array_diff($newGroupIds, $currentGroupIds) as $addGroupId) {
            $this->groups()->attach($addGroupId);
        }

        /*
         * Save other page info
         */
        if ($willPublish && array_key_exists('menus', $pageInfoOther) &&  Auth::action('menus')) {
            MenuItem::set_page_menus($this->id, $pageInfoOther['menus']);
        }

        if ($canPublish && array_key_exists('beacons', $pageInfoOther) && Auth::action('themes.beacons-update')) {
            BlockBeacon::updatePage($this->id, $pageInfoOther['beacons']);
        }

        return true;
    }

    /**
     * Saves page data as new page (will update page groups)
     * @param array $pagePost
     * @param array $pageLangPost
     * @param array $pageGroupsPost
     * @param array $pageInfoOther
     * @return Page|false
     */
    public function saveDuplicateFromPostData($pagePost, $pageLangPost, $pageGroupsPost, $pageInfoOther = [])
    {
        /** @var Page $duplicatePage */
        $duplicatePage = $this->replicate();
        $duplicatePage->setRelations([]);

        $pageLangPost['name'] = preg_replace('/\s+Duplicate$/', '', $pageLangPost['name']) . ' Duplicate';
        $pageLangPost['url'] = preg_replace('/--v\w+$/', '', $pageLangPost['url']) . '--v' . base_convert(microtime(true), 10, 36);
        $pageVersion = PageVersion::prepareNew();

        if ($duplicatePage->savePostData($pageVersion, $pagePost, $pageLangPost, $pageGroupsPost, $pageInfoOther)) {

            // duplicate role actions from original page
            foreach (UserRole::all() as $role) {
                /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $pageActionsRelation */
                $pageActionsRelation = $role->page_actions();
                /** @var Collection $pageActions */
                $pageActions = $pageActionsRelation->where('page_id', '=', $duplicatePage->id)->get();
                if (!$pageActions->isEmpty()) {
                    foreach ($pageActions as $pageAction) {
                        $pageActionsRelation->attach($duplicatePage->id, ['action_id' => $pageAction->pivot->action_id, 'access' => $pageAction->pivot->access]);
                    }
                }
            }

            return $duplicatePage;
        } else {
            return false;
        }
    }

}
