<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageLoadException;
use CoasterCms\Helpers\Feed;
use CoasterCms\Helpers\View\PaginatorRender;
use CoasterCms\Libraries\Blocks\Image;
use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\Menu;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class PageBuilder
{

    public static $theme = 'default';
    public static $page_info;
    public static $page_levels;
    public static $page_id;

    public static $external_template = false;
    public static $search_not_found = false;
    public static $preview = false;

    private static $_cat_links = array();

    private static $_custom_block_data = array();
    private static $_custom_block_data_current_key = 0;

    public static function set_page($theme_id)
    {

        // load theme/template
        $theme = Theme::find($theme_id);
        if (!empty($theme) && is_dir(base_path('/resources/views/themes/' . $theme->theme))) {
            self::$theme = $theme->theme;
        }

        // load language
        $check_in_language = array(Language::current());
        if (config('coaster::frontend.language_fallback') == 1) {
            $check_in_language[] = config('coaster::frontend.language');
        }

        // load page info
        $url_parts = count(Request::segments());
        $page_level = array();

        // check root
        if ($url_parts == 1 && substr(Request::segment(1), 0, 5) == 'root.') {
            if (Feed::$extension = Feed::check_exists(self::$theme, Request::segment(1))) {
                $url_parts = 0;
            }
        }

        foreach ($check_in_language as $language) {
            // check for home page
            if (empty($url_parts)) {
                $page_level[1] = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where(function ($query) {
                    $query->where('page_lang.url', '=', '/')->orWhere('page_lang.url', '=', '');
                })->where('page_lang.language_id', '=', $language)->where('parent', '=', 0)->first();
                if (!empty($page_level[1])) {
                    $url_parts = 1;
                    break;
                }
            } // check internals
            else {
                $page_level[0] = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where(function ($query) {
                    $query->where('page_lang.url', '=', '/')->orWhere('page_lang.url', '=', '');
                })->where('page_lang.language_id', '=', $language)->where('parent', '=', 0)->first();
                if (!empty($page_level[0])) {
                    $parent = 0;
                    for ($i = 1; $i <= $url_parts; $i++) {
                        $current_segment = Request::segment($i);
                        // check for feed templates
                        if ($url_parts == $i) {
                            if (Feed::$extension = Feed::check_exists(self::$theme, $current_segment)) {
                                $current_segment = substr($current_segment, 0, -(1 + strlen(Feed::$extension)));
                            }
                        }
                        if ($i > 1) {
                            $parent = $page_level[$i - 1]->page_id;
                        }
                        $page_level[$i] = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where('page_lang.url', '=', $current_segment)->where('page_lang.language_id', '=', $language)->where('parent', '=', $parent)->first();
                        if (empty($page_level[$i])) {
                            // group page check
                            if ($i == $url_parts && $page_level[$i - 1]->group_container > 0) {
                                $page_level[$i] = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where('page_lang.url', '=', $current_segment)->where('page_lang.language_id', '=', $language)->where('in_group', '=', $page_level[$i - 1]->group_container)->first();
                            }
                            // check for search query
                            if (empty($page_level[$i]) && in_array('search', array($current_segment, Request::segment($i - 1)))) {
                                unset($page_level[$i]);
                                $url_parts = $i - 1;
                                self::$search_not_found = true;
                            }
                            break;
                        }
                    }
                    if (!empty($page_level[$i])) {
                        break;
                    }
                }
            }
        }

        // if page was not found, 404
        if (empty($page_level[$url_parts])) {
            throw new PageLoadException('page not found');
        } else {
            self::$page_id = $page_level[$url_parts]->page_id;
            self::$page_info = $page_level[$url_parts];
            self::$page_levels = $page_level;
        }

        $preview_key = Request::input('preview');
        if (!empty($preview_key)) {
            $page_version = PageVersion::where('page_id', '=', self::$page_info->page_id)->where('preview_key', '=', $preview_key)->first();
            if (!empty($page_version)) {
                self::$page_info->live_version = $page_version->version_id;
                self::$page_info->template = $page_version->template;
                self::$preview = true;
            }
        }

        // check if live when not previewing
        if (!self::$preview) {
            $page = Page::preload(self::$page_info->page_id);
            if (!$page->is_live()) {
                throw new PageLoadException('page not live');
            }
        }

        // if template not found 404
        self::$page_info->template_name = Template::name(self::$page_info->template);
        if (!View::exists('themes.' . self::$theme . '.' . (Feed::$extension ? 'feed.' . Feed::$extension : 'templates') . '.' . self::$page_info->template_name)) {
            throw new PageLoadException('template not found');
        }

    }

    public static function page_id()
    {
        if (!empty(self::$page_info)) {
            return self::$page_info->page_id;
        }
    }

    public static function parent_id()
    {
        $levels = count(self::$page_levels);
        if ($levels >= 2) {
            return self::$page_levels[$levels - 2]->page_id;
        } else {
            return null;
        }
    }

    public static function page_template()
    {
        if (!empty(self::$page_info->template)) {
            return self::$page_info->template;
        } else {
            return 0;
        }
    }

    public static function external($section)
    {
        if (View::exists('themes.' . self::$theme . '.externals.' . $section))
            return View::make('themes.' . self::$theme . '.externals.' . $section);
        else
            return 'View not found';
    }

    public static function get_template_path()
    {
        return 'themes.' . self::$theme . '.' . (Feed::$extension ? 'feed.' . Feed::$extension : 'templates') . '.' . self::$page_info->template_name;
    }

    public static function page_url($page_id = 0, $real_url = false)
    {
        if ($real_url && isset(self::$page_info->true_page_id)) {
            $page_id = self::$page_info->true_page_id;
        }
        if (empty($page_id)) {
            if (empty(self::$page_info)) {
                return null;
            }
            $page_id = self::$page_info->page_id;
        }
        return PageLang::full_url($page_id);
    }

    public static function page_name($page_id = 0)
    {
        if (empty($page_id)) {
            if (empty(self::$page_info)) {
                return '404';
            }
            return self::$page_info->name;
        } else {
            return PageLang::name($page_id);
        }
    }

    public static function page_full_name($page_id = 0, $sep = ' &raquo; ')
    {
        if (empty($page_id)) {
            if (empty(self::$page_info)) {
                return '404';
            }
            $page_id = self::$page_info->page_id;
        }
        return PageLang::full_name($page_id, $sep);

    }

    public static function section($section)
    {
        return View::make('themes.' . self::$theme . '.sections.' . $section)->render();
    }

    public static function sitemap($options = array())
    {
        $topLevelPages = Page::where('parent', '=', 0)->where('in_group', '=', 0)->get();
        $topLevelPages = $topLevelPages->isEmpty() ? [] : $topLevelPages;
        foreach ($topLevelPages as $key => $page) {
            if (!$page->is_live()) {
                unset($topLevelPages[$key]);
            }
        }
        return self::_cat_view(0, $topLevelPages, $options);
    }

    public static function category($options = array())
    {
        if (empty($options['page_id']) && !empty(self::$page_info)) {
            $page_id = self::$page_info->page_id;
        } else {
            $page_id = (int)$options['page_id'];
        }
        if ($page_id > 0) {
            $pages = Page::category_pages($page_id, true);
            if (!empty($pages)) {
                return self::_cat_view($page_id, $pages, $options);
            }
        }
        return null;
    }

    public static function filter($block_name, $search, $options = array())
    {
        if (empty($options['page_id']) && !empty(self::$page_info)) {
            $page_id = self::$page_info->page_id;
        } else {
            $page_id = (int)$options['page_id'];
        }
        $default_options = array(
            'match' => '='
        );
        $options = array_merge($default_options, $options);
        $block = Block::preload($block_name);
        $block_type = $block->get_class();

        $page_ids = $block_type::filter($block->id, $search, $options['match']);
        $pages = [];
        foreach ($page_ids as $page_id) {
            $pages[] = Page::preload($page_id);
        }
        return self::_cat_view($page_id, $pages, $options);
    }

    public static function category_filter($block_name, $search, $options = array())
    {
        if (empty($options['page_id']) && !empty(self::$page_info)) {
            $page_id = self::$page_info->page_id;
        } else {
            $page_id = (int)$options['page_id'];
        }
        if ($page_id > 0) {
            $default_options = array(
                'match' => '='
            );
            $pages = Page::category_pages($page_id, true);
            $options = array_merge($default_options, $options);
            $block = Block::preload($block_name);
            $block_type = $block->get_class();
            $page_ids = $block_type::filter($block->id, $search, $options['match']);
            $filtered_pages = [];
            foreach ($pages as $page) {
                if (in_array($page->id, $page_ids)) {
                    $filtered_pages[] = $page;
                }
            }
            return self::_cat_view($page_id, $filtered_pages, $options);
        }
        return null;
    }

    public static function category_link($direction = 'next')
    {
        if (!isset(self::$_cat_links[$direction])) {
            self::$_cat_links['next'] = '';
            self::$_cat_links['prev'] = '';
            $levels = count(self::$page_levels);
            if (!empty(self::$page_levels[$levels - 2])) {
                $parent_id = self::$page_levels[$levels - 2]->page_id;
            }
            if (!empty($parent_id)) {
                $pages = Page::category_pages($parent_id, true);
                if (count($pages) > 1) {
                    foreach ($pages as $k => $page) {
                        if ($page->id == self::$page_info->page_id) {
                            $key = $k;
                            break;
                        }
                    }
                    if (isset($key)) {
                        if (!empty($pages[$key + 1])) {
                            self::$_cat_links['next'] = PageLang::full_url($pages[$key + 1]->id);//change url function to ../
                        }
                        if (!empty($pages[$key - 1])) {
                            self::$_cat_links['prev'] = PageLang::full_url($pages[$key - 1]->id);
                        }
                    }
                }
            }
        }
        return self::$_cat_links[$direction];
    }

    public static function search($options = array())
    {
        self::$search_not_found = false;
        // get query (should be after last 'search' segment in url)
        $search_pos = array_search('search', array_reverse(Request::segments(), true));
        if ($search_pos !== false) {
            $query = urldecode(Request::segment($search_pos + 2)); // + 2 due to segments starting at 1
        } else {
            $query = '';
        }
        $pages = array();
        if ($query != '') {
            $pages = PageSearchData::lookup($query);
            if (!empty($pages)) {
                if (!empty($options['templates'])) {
                    foreach ($pages as $k => $page) {
                        if (!isset($page->template) || !in_array($page->template, $options['templates'])) {
                            unset($pages[$k]);
                        }
                    }
                }
                $results = count($pages);
                $showing = "";
                if ($results > 20) {
                    $page = (int)Request::input('page');
                    if ($page == 0) {
                        $page = 1;
                    }
                    $max = (($page * 20) > $results) ? $results : ($page * 20);
                    $showing = " [showing " . (($page - 1) * 20 + 1) . " - " . $max . "]";
                }
                $options['content'] = "Search results for '" . $query . "' (" . $results . " match" . ((count($pages) > 1) ? 'es' : null) . " found)" . $showing . ":";
            } else {
                $options['content'] = "No results found for '" . $query . "'.";
            }
        } else {
            $options['content'] = array_key_exists('content', $options) ? $options['content'] : "No search query entered.";
        }
        return self::_cat_view(0, $pages, $options);
    }

    public static function block($block_name, $options = array())
    {
        $is_global = false;
        $block = Block::preload($block_name, isset($options['force_query']));
        // load block content for current repeater (if preloaded)
        if (($custom_data = self::_load_custom_block_data($block_name)) !== false) {
            $block_content = $custom_data;
        } // load block content
        elseif (!empty($block)) {
            $default_block = PageBlockDefault::preload_block($block->id);
            // load array of language => custom page block, for current page or page_id override
            if (!empty($options['page_id'])) {
                $string = explode(',', $options['page_id']); // if comma remove it (only used for group page url)
                $selected_page = PageLang::preload($string[0]);
                $custom_page_block = PageBlock::preload_page_block($string[0], $block->id, $selected_page->live_version);
                unset($options['page_id']);
            } elseif (!empty(self::$page_info)) {
                $custom_page_block = PageBlock::preload_page_block(self::$page_info->page_id, $block->id, self::$page_info->live_version);
            }
            // if custom page block for selected language exists
            if (!empty($custom_page_block[Language::current()])) {
                $block_content = $custom_page_block[Language::current()]->content;
            } // if default block for selected language exists
            elseif (!empty($default_block[Language::current()])) {
                $block_content = $default_block[Language::current()]->content;
                $is_global = true;
            } // if custom page block for default site language exists
            elseif (config('coaster::frontend.language_fallback') == 1 && !empty($custom_page_block[config('coaster::frontend.language')])) {
                $block_content = $custom_page_block[config('coaster::frontend.language')]->content;
            } // if default block for default site language exists
            elseif (config('coaster::frontend.language_fallback') == 1 && !empty($default_block[config('coaster::frontend.language')])) {
                $block_content = $default_block[config('coaster::frontend.language')]->content;
                $is_global = true;
            } // else no block data found for selected or default site language
            else {
                $block_content = null;
            }
        } // error block not found
        elseif (env('APP_ENV') == 'development') {
            $type = 'string';
            $typesArr = ['intro' => 'text', 'content' => 'richtext', 'image' => 'image', 'link' => 'link', 'link_text' => 'string'];
            foreach ($typesArr as $tsrch => $t) {
                if (stristr($block_name, $tsrch) !== FALSE) {
                    $type = $t;
                }
            }
            $lbl = ucwords(str_replace('_', ' ', $block_name));
            Block::unguard();
            Block::create(['label' => $lbl, 'name' => $block_name, 'type' => $type, 'category_id' => 1])->save();
            Block::reguard();
            $options['force_query'] = true;
            return self::block($block_name, $options);
        } else {
            return 'block not found';
        }

        if (isset($options['raw']) && $options['raw']) {
            return $block_content;
        }

        // pass block details and content to display class
        $block_type = $block->get_class();
        $options['version'] = 0;
        if (ucwords($block->type) == 'Repeater' && !$is_global) {
            if (!empty($selected_page)) {
                $options['version'] = $selected_page->live_version;
            } else {
                $options['version'] = !empty(self::$page_info->live_version) ? self::$page_info->live_version : 0;
            }
        }

        return $block_type::display($block, $block_content, $options);
    }

    public static function breadcrumb($options = array())
    {
        $default_options = array(
            'view' => 'default'
        );
        $options = array_merge($default_options, $options);
        if (!View::exists('themes.' . self::$theme . '.breadcrumbs.' . $options['view'] . '.wrap')) {
            return null;
        }
        $crumbs = '';
        if (!empty(self::$page_levels)) {
            $url = '';
            end(self::$page_levels);
            $active_key = key(self::$page_levels);
            foreach (self::$page_levels as $key => $crumb) {
                if ($crumb->url != '/') {
                    // if not homepage, build up uri
                    $url .= '/' . $crumb->url;
                    $crumb->url = $url;
                }
                if ($active_key == $key) {
                    $crumbs .= View::make('themes.' . self::$theme . '.breadcrumbs.' . $options['view'] . '.active_element', array('crumb' => $crumb));
                } else {
                    $crumbs .= View::make('themes.' . self::$theme . '.breadcrumbs.' . $options['view'] . '.link_element', array('crumb' => $crumb));
                    $crumbs .= View::make('themes.' . self::$theme . '.breadcrumbs.' . $options['view'] . '.separator');
                }
            }
        }
        return View::make('themes.' . self::$theme . '.breadcrumbs.' . $options['view'] . '.wrap', array('crumbs' => $crumbs));
    }

    public static function menu($menu_name, $options = array())
    {
        $menu = Menu::get_menu($menu_name);
        if (!empty($menu)) {
            $default_options = array(
                'view' => 'default'
            );
            $options = array_merge($default_options, $options);
            if (!MenuBuilder::set_view($options['view'])) {
                return null;
            }
            return MenuBuilder::build_menu($menu->items()->get(), 1);
        } else {
            return null;
        }
    }

    public static function img($file_name, $options = array())
    {
        $image_data = new \stdClass;
        $image_data->file = '/themes/' . self::$theme . '/img/' . $file_name;
        return Image::display(null, $image_data, $options);
    }

    public static function css($file_name)
    {
        return URL::to('/themes/' . self::$theme . '/css/' . $file_name . '.css');
    }

    public static function js($file_name)
    {
        return URL::to('/themes/' . self::$theme . '/js/' . $file_name . '.js');
    }

    public static function set_custom_block_data($block_name, $content)
    {
        if (!isset(self::$_custom_block_data[self::$_custom_block_data_current_key])) {
            self::$_custom_block_data[self::$_custom_block_data_current_key] = array();
        }
        self::$_custom_block_data[self::$_custom_block_data_current_key][$block_name] = $content;
    }

    public static function set_custom_block_data_key($key = 0)
    {
        self::$_custom_block_data = $key;
    }

    private static function _cat_view($cat_id, $pages, $options)
    {
        $default_options = array(
            'view' => 'default',
            'type' => 'all',
            'per_page' => 20,
            'limit' => 0,
            'content' => ''
        );
        $options = array_merge($default_options, $options);
        // select page of selected type
        $page_list = array();
        if ($options['type'] == 'all') {
            foreach ($pages as $page) {
                $page_list[] = $page;
            }
        } else {
            foreach ($pages as $page) {
                $children = count(Page::child_page_ids($page->id));
                if (($options['type'] == 'pages' && $children == 0) || ($options['type'] == 'categories' && $children > 0)) {
                    $page_list[] = $page;
                }
            }
        }
        if (!empty($options['limit']) && is_int($options['limit'])) {
            $page_list = array_slice($page_list, 0, $options['limit']);
        }
        // pagination
        if (!empty($options['per_page']) && (int)$options['per_page'] > 0) {
            $pages_paginator = new LengthAwarePaginator($page_list, count($page_list), $options['per_page'], Request::input('page', 1));
            $pages_paginator->setPath(Request::getPathInfo());
            $links = PaginatorRender::run($pages_paginator);
            $pages = array_slice($page_list, (($pages_paginator->currentPage() - 1) * $options['per_page']), $options['per_page']);
        } else {
            $pages = $page_list;
            $links = null;
        }
        $list = '';
        $is_first = true;
        $is_last = false;
        $total_pages = count($pages);
        $count = 1;
        $cat_path = '';
        if ($cat_id > 0) {
            $cat_page = Page::preload($cat_id);
            if ($cat_page->group_container > 0) {
                $cat_path = ',' . $cat_page->id;
            }
        }
        foreach ($pages as $page) {
            if ($total_pages == $count) {
                $is_last = true;
            }
            if ($page->id > 0) {
                $paths = PageLang::get_full_path($page->id . $cat_path);
                $page_info = new \stdClass;
                $page_info->id = $page->id;
                $page_info->name = $paths->name;
                $page_info->full_name = $paths->full_name;
                $page_info->url = $paths->full_url;
            } else {
                $page_info = $page;
            }
            // temp overwrite current page variables so basic PageBuilder functions work relative to category page
            $page_info->true_page_id = self::$page_id;
            if (!empty(self::$page_info)) {
                $original_page_info = clone self::$page_info;
            } else {
                self::$page_info = new \stdClass;
            }
            self::$page_info->true_page_id = $page_info->true_page_id;
            self::$page_info->page_id = $page_info->id;
            self::$page_info->name = $page_info->name;
            self::$page_info->live_version = PageLang::preload($page->id)->live_version;
            self::$page_info->template = $page->template;
            $list .= View::make('themes.' . self::$theme . '.categories.' . $options['view'] . '.page', array('page' => $page_info, 'category_id' => $cat_id, 'is_first' => $is_first, 'is_last' => $is_last, 'count' => $count, 'total' => $total_pages))->render();
            if (!empty($original_page_info)) {
                self::$page_info = $original_page_info;
                unset($original_page_info);
            } else {
                self::$page_info = null;
            }
            $count++;
            $is_first = false;
        }
        $html_content = (!empty($options['html']) && $options['html']) ? true : false;
        return View::make('themes.' . self::$theme . '.categories.' . $options['view'] . '.pages_wrap', array('pages' => $list, 'pagination' => $links, 'links' => $links, 'content' => $options['content'], 'category_id' => $cat_id, 'total' => $total_pages, 'html_content' => $html_content))->render();
    }

    private static function _load_custom_block_data($block_name)
    {
        if (isset(self::$_custom_block_data[self::$_custom_block_data_current_key][$block_name])) {
            return self::$_custom_block_data[self::$_custom_block_data_current_key][$block_name];
        }
        return Repeater::load_repeater_data($block_name);
    }

}
