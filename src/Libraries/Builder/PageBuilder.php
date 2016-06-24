<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Helpers\Core\Page\PageLoader;
use CoasterCms\Helpers\Core\Page\Search;
use CoasterCms\Helpers\Core\View\Classes\BreadCrumb;
use CoasterCms\Helpers\Core\View\Classes\FullPage;
use CoasterCms\Helpers\Core\View\PaginatorRender;
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
use CoasterCms\Models\Setting;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Request;
use URL;
use View;

class PageBuilder
{

    /**
     * @var Page
     */
    public static $pageOverride;

    /**
     * @var Page
     */
    public static $page;

    /**
     * @var Page[]
     */
    public static $pageLevels;

    /**
     * @var string
     */
    public static $template;

    /**
     * @var string
     */
    public static $theme;

    /**
     * @var bool
     */
    public static $is404;

    /**
     * @var bool
     */
    public static $isPreview;

    /**
     * @var bool
     */
    public static $isLive;

    /**
     * @var string|false
     */
    public static $externalTemplate;

    /**
     * @var string|false
     */
    public static $feedExtension;

    /**
     * @var string|false
     */
    public static $searchQuery;

    /**
     * @var array
     */
    protected static $_pageCategoryLinks;

    /**
     * @var array
     */
    protected static $_customBlockData;

    /**
     * @var int
     */
    protected static $_customBlockDataKey;

    /**
     * @param int $themeId
     */
    public static function setTheme($themeId)
    {
        $theme = Theme::find($themeId);
        if (!empty($theme) && is_dir(base_path('/resources/views/themes/' . $theme->theme))) {
            self::$theme = $theme->theme;
        } else {
            self::$theme = 'default';
        }
    }

    /**
     * @param PageLoader $pageLoader
     */
    public static function setPageFromLoader(PageLoader $pageLoader)
    {
        $currentPageIndex = count($pageLoader->pageLevels)-1;

        self::$page = $currentPageIndex >= 0 ? $pageLoader->pageLevels[$currentPageIndex] : null;
        self::$pageLevels = $pageLoader->pageLevels;

        self::$template = Template::name(self::$page->template);

        self::$is404 = $pageLoader->is404;
        self::$isPreview = $pageLoader->isPreview;
        self::$isLive = $pageLoader->isLive;
        self::$externalTemplate = $pageLoader->externalTemplate;
        self::$feedExtension = $pageLoader->feedExtension;
        self::$searchQuery = $pageLoader->searchQuery;
    }
    
    /**
     * @param bool $noOverride
     * @return int
     */
    public static function pageId($noOverride = false)
    {
        $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
        return !empty($page) ? $page->id : 0;
    }

    /**
     * @return int
     */
    public static function parentPageId()
    {
        $levels = count(self::$pageLevels);
        return $levels > 1 ? self::$pageLevels[$levels - 2]->id : 0;
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public static function pageTemplateId($noOverride = false)
    {
        $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
        return !empty($page) ? $page->template : 0;
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public static function pageLiveVersionId($noOverride = false)
    {
        $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
        return (!empty($page) && !$page->page_lang->isEmpty()) ? $page->page_lang[0]->live_version : 0;
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public static function pageUrlSegment($pageId = 0, $noOverride = false)
    {
        if (!$pageId) {
            $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
            $pageId = !empty($page) && !empty($page->id) ? $page->id : 0;
        }
        return $pageId ? PageLang::url($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public static function pageUrl($pageId = 0, $noOverride = false)
    {
        if (!$pageId) {
            $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
            $pageId = !empty($page) && !empty($page->id) ? $page->id : 0;
        }
        return $pageId ? PageLang::full_url($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public static function pageName($pageId = 0, $noOverride = false)
    {
        if (!$pageId) {
            $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
            $pageId = !empty($page) && !empty($page->id) ? $page->id : 0;
        }
        return $pageId ? PageLang::name($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @param string $sep
     * @return string
     */
    public static function pageFullName($pageId = 0, $noOverride = false, $sep = ' &raquo; ')
    {
        if (!$pageId) {
            $page = (self::$pageOverride && !$noOverride) ? self::$pageOverride : self::$page;
            $pageId = !empty($page) && !empty($page->id) ? $page->id : 0;
        }
        return $pageId ? PageLang::full_name($pageId, $sep): '';
    }

    /**
     * @param string $fileName
     * @param array $options
     * @return string
     */
    public static function img($fileName, $options = [])
    {
        $image_data = new \stdClass;
        $image_data->file = '/themes/' . self::$theme . '/img/' . $fileName;
        return Image::display(null, $image_data, $options);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public static function css($fileName)
    {
        return URL::to('/themes/' . self::$theme . '/css/' . $fileName . '.css');
    }

    /**
     * @param string $fileName
     * @return string
     */
    public static function js($fileName)
    {
        return URL::to('/themes/' . self::$theme . '/js/' . $fileName . '.js');
    }

    /**
     * @param string $blockName
     * @param mixed $content
     * @param int $key
     */
    public static function setCustomBlockData($blockName, $content, $key = 0)
    {
        if (!isset(self::$_customBlockData)) {
            self::$_customBlockData = [];
            self::$_customBlockDataKey = 0;
        }
        if (!isset(self::$_customBlockData[$key])) {
            self::$_customBlockData[$key] = [];
        }
        self::$_customBlockData[$key][$blockName] = $content;
    }

    /**
     * @param string|int $key
     */
    public static function setCustomBlockDataKey($key)
    {
        self::$_customBlockDataKey = $key;
    }

    /**
     * @param string $section
     * @return string
     */
    public static function external($section)
    {
        return self::_getRenderedView('externals.' . $section);
    }

    /**
     * @param string $section
     * @return string
     */
    public static function section($section)
    {
        return self::_getRenderedView('sections.' . $section);
    }

    /**
     * @param array $options
     * @return string
     */
    public static function breadcrumb($options = [])
    {
        $defaultOptions = [
            'view' => 'default'
        ];
        $options = array_merge($defaultOptions, $options);

        $crumbs = '';
        if (!empty(self::$pageLevels)) {
            $url = '';
            $lowestLevel = count(self::$pageLevels)-1;
            foreach (self::$pageLevels as $level => $page) {

                if ($page->page_lang[0]->url != '/') {
                    $url .= '/' . $page->page_lang[0]->url;
                }
                $active = ($lowestLevel == $level);

                $crumb = new BreadCrumb($page->page_lang[0], $url, $active);

                if (self::_viewExists('.breadcrumbs.' . $options['view'] . '.active_element') && $active) {
                    $crumbs .= self::_getRenderedView('breadcrumbs.' . $options['view'] . '.active_element', ['crumb' => $crumb]);
                } else {
                    $crumbs .= self::_getRenderedView('breadcrumbs.' . $options['view'] . '.link_element', ['crumb' => $crumb]);
                    $crumbs .= ($active ? self::_getRenderedView('breadcrumbs.' . $options['view'] . '.separator') : '');
                }
            }
        }
        return self::_getRenderedView('breadcrumbs.' . $options['view'] . '.wrap', ['crumbs' => $crumbs]);
    }

    /**
     * @param $menuName
     * @param array $options
     * @return string
     */
    public static function menu($menuName, $options = [])
    {
        $menu = Menu::get_menu($menuName);
        if (!empty($menu)) {
            $defaultOptions = [
                'view' => 'default'
            ];
            $options = array_merge($defaultOptions, $options);
            MenuBuilder::setView($options['view']);
            return MenuBuilder::buildMenu($menu->items()->get(), 1);
        } else {
            return '';
        }
    }

    /**
     * @param array $options
     * @return string
     */
    public static function sitemap($options = [])
    {
        $topLevelPages = Page::where('parent', '=', 0)->where('in_group', '=', 0)->get();
        $topLevelPages = $topLevelPages->isEmpty() ? [] : $topLevelPages;
        foreach ($topLevelPages as $key => $page) {
            if (!$page->is_live() || !$page->sitemap) {
                unset($topLevelPages[$key]);
            }
        }
        return self::_renderCategory(0, $topLevelPages, $options);
    }

    /**
     * @param array $options
     * @return string
     */
    public static function category($options = [])
    {
        $pageId = !empty($options['page_id']) ? $options['page_id'] : self::pageId();
        if ($pageId) {
            $pages = Page::category_pages($pageId, true);
            if (!empty($pages)) {
                if (!empty($options['sitemap'])) {
                    foreach ($pages as $key => $page) {
                        if (!$page->sitemap) {
                            unset($pages[$key]);
                        }
                    }
                }
                return self::_renderCategory($pageId, $pages, $options);
            }
        }
        return '';
    }

    /**
     * @param string $direction
     * @return string
     */
    public static function categoryLink($direction = 'next')
    {
        if (!isset(self::$_pageCategoryLinks)) {
            self::$_pageCategoryLinks = [
                'next' => '',
                'prev' => ''
            ];
            $parentPageId = self::parentPageId();
            if ($parentPageId) {
                $pages = Page::category_pages($parentPageId, true);
                if (count($pages) > 1) {
                    foreach ($pages as $k => $page) {
                        if ($page->id == self::$page->id) {
                            $key = $k;
                            break;
                        }
                    }
                    if (isset($key)) {
                        if (!empty($pages[$key + 1])) {
                            self::$_pageCategoryLinks['next'] = PageLang::full_url($pages[$key + 1]->id);
                        }
                        if (!empty($pages[$key - 1])) {
                            self::$_pageCategoryLinks['prev'] = PageLang::full_url($pages[$key - 1]->id);
                        }
                    }
                }
            }
        }
        return self::$_pageCategoryLinks[$direction];
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public static function filter($blockName, $search, $options = [])
    {
        $defaultOptions = [
            'match' => '='
        ];
        $options = array_merge($defaultOptions, $options);

        $block = Block::preload($blockName);
        $blockType = $block->get_class();

        $filteredPages = [];
        $filterPageIds = $blockType::filter($block->id, $search, $options['match']);
        foreach ($filterPageIds as $filterPageId) {
            $filteredPages[] = Page::preload($filterPageId);
        }

        $pageId = !empty($options['page_id']) ? $options['page_id'] : self::pageId();
        return self::_renderCategory($pageId, $filteredPages, $options);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public static function categoryFilter($blockName, $search, $options = [])
    {
        $pageId = !empty($options['page_id']) ? $options['page_id'] : self::pageId();
        if ($pageId) {

            $defaultOptions = [
                'match' => '='
            ];
            $options = array_merge($defaultOptions, $options);


            $block = Block::preload($blockName);
            $blockType = $block->get_class();

            $filteredPages = [];
            $filterPageIds = $blockType::filter($block->id, $search, $options['match']);
            $categoryPages = Page::category_pages($pageId, true);
            foreach ($categoryPages as $categoryPage) {
                if (in_array($categoryPage->id, $filterPageIds)) {
                    $filteredPages[] = $categoryPage;
                }
            }
            return self::_renderCategory($pageId, $filteredPages, $options);
        }
        return '';
    }

    /**
     * @param array $options
     * @return string
     */
    public static function search($options = [])
    {
        Search::$searchBlock = true;
        // get query (should be after last 'search' segment in url)
        $search_pos = array_search('search', array_reverse(Request::segments(), true));
        if ($search_pos !== false && Request::segment($search_pos + 2)) {
            $query = urldecode(Request::segment($search_pos + 2)); // + 2 due to segments starting at 1
        } else {
            $query = Request::get('q');
        }
        $options['search_query'] = $query;
        $pages = [];
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

        return self::_renderCategory(0, $pages, $options);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public static function block($blockName, $options = [])
    {
        // force query available if block details changed in current request
        $block = Block::preload($blockName, isset($options['force_query']));

        $usingGlobalContent = false;
        $blockData = null;

        if (!empty($options['page_id'])) {
            $string = explode(',', $options['page_id']); // if comma remove it (only used for group page url)
            $pageId = $string[0];
            $selectedPage = PageLang::preload($string[0]);
            $pageVersionId = !empty($selectedPage) ? $selectedPage->live_version : 0;
        } else {
            $pageId = !empty(self::$page) ? self::$page->id : 0;
            $pageVersionId = $pageId ? self::$page->page_lang[0]->live_version : 0;
        }

        if (($customBlockData = self::_getCustomBlockData($blockName)) !== false) {
            // load custom block data for (is also used for repeater content)
            $blockData = $customBlockData;
        } elseif (!empty($block)) {

            // load block data
            $globalBlockData = PageBlockDefault::preload_block($block->id);
            $pageBlockData = PageBlock::preload_page_block($pageId, $block->id, $pageVersionId);

            // get languages
            $loadForLanguages = [Language::current()];
            if (config('coaster::frontend.language_fallback') == 1 && !in_array(config('coaster::frontend.language'), $loadForLanguages)) {
                $loadForLanguages[] = config('coaster::frontend.language');
            }

            // run through languages until block data found
            foreach ($loadForLanguages as $language) {
                if (!empty($pageBlockData[$language])) {
                    // if custom page block for selected language exists
                    $blockData = $pageBlockData[$language]->content;
                    break;
                } elseif (!empty($globalBlockContent[$language])) {
                    // if default block for selected language exists
                    $blockData = $globalBlockData[$language]->content;
                    $usingGlobalContent = true;
                    break;
                }
            }

            // return raw data
            if (isset($options['raw']) && $options['raw']) {
                return $blockData;
            }
        } else {
            return 'block not found';
        }

        // set version that data has been grabbed for (0 = latest)
        if(empty($options['version'])) {
            $options['version'] = $usingGlobalContent ? 0 : $pageVersionId;
        }

        // pass block details and data to display class
        $blockType = $block->get_class();
        return $blockType::display($block, $blockData, $options);
    }

    /**
     * @param int $getPosts
     * @param string $where
     * @return \PDOStatement
     */
    public static function blogPosts($getPosts = 3, $where = 'post_type = "post" AND post_status = "publish"')
    {
        $prefix = config('coaster::blog.prefix');
        $where = $where ? 'WHERE ' . $where : '';
        $query = "SELECT * FROM {$prefix}posts {$where} ORDER BY post_date DESC LIMIT {$getPosts}";
        try {
            return Setting::blogConnection()->query($query);
        } catch (\Exception $e) {
            return new \PDOStatement;
        }
    }

    /**
     * @param string $viewPath
     * @return bool
     */
    protected static function _viewExists($viewPath)
    {
        $viewPath = 'themes.' . self::$theme . '.' . $viewPath;
        return View::exists($viewPath);
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @return string
     */
    protected static function _getRenderedView($viewPath, $data = [])
    {
        $viewPath = 'themes.' . self::$theme . '.' . $viewPath;
        if (View::exists($viewPath)) {
            return View::make($viewPath, $data)->render();
        } else {
            return 'View not found (' . $viewPath . ')';
        }
    }

    /**
     * @param int $categoryPageId
     * @param Page[]  $pages
     * @param array $options
     * @return string
     */
    protected static function _renderCategory($categoryPageId, $pages, $options)
    {
        $defaultOptions = [
            'view' => 'default',
            'type' => 'all',
            'per_page' => 20,
            'limit' => 0,
            'content' => '',
            'search_query' => ''
        ];
        $options = array_merge($defaultOptions, array_filter($options));

        // select page of selected type
        $pagesOfSelectedType = [];
        if ($options['type'] == 'all') {
            $pagesOfSelectedType = is_a($pages, Collection::class) ? $pages->all() : $pages;
        } else {
            foreach ($pages as $page) {
                $children = count(Page::child_page_ids($page->id));
                if (($options['type'] == 'pages' && $children == 0) || ($options['type'] == 'categories' && $children > 0)) {
                    $pagesOfSelectedType[] = $page;
                }
            }
        }

        // limit results
        if (!empty($options['limit']) && is_int($options['limit'])) {
            $pagesOfSelectedType = array_slice($pagesOfSelectedType, 0, $options['limit']);
        }

        // pagination
        if (!empty($options['per_page']) && (int)$options['per_page'] > 0) {
            $paginator = new LengthAwarePaginator($pagesOfSelectedType, count($pagesOfSelectedType), $options['per_page'], Request::input('page', 1));
            $paginator->setPath(Request::getPathInfo());
            $paginationLinks = PaginatorRender::run($paginator);
            $pages = array_slice($pagesOfSelectedType, (($paginator->currentPage() - 1) * $options['per_page']), $options['per_page']);
        } else {
            $pages = $pagesOfSelectedType;
            $paginationLinks = null;
        }

        $list = '';
        $total = count($pages);

        $categoryPath = '';
        if ($categoryPageId) {
            $categoryPage = Page::preload($categoryPageId);
            $categoryPath = ($categoryPage && $categoryPage->group_container > 0) ? ',' . $categoryPage->id : '';
        }

        $pages = array_values($pages);
        foreach ($pages as $count => $page) {
            $isFirst = ($count == 1);
            $isLast = ($count == $total);

            $page->page_lang = (!$page->page_lang->isEmpty())?$page->page_lang[0]:PageLang::preload($page->id);
            $fullPageInfo = new FullPage($page, $page->page_lang, $categoryPath);

            self::$pageOverride = $page;

            $list .= self::_getRenderedView(
                'categories.' . $options['view'] . '.page',
                ['page' => $fullPageInfo, 'category_id' => $categoryPageId, 'is_first' => $isFirst, 'is_last' => $isLast, 'count' => $count, 'total' => $total]
            );

            self::$pageOverride = null;
        }

        return self::_getRenderedView(
            'categories.' . $options['view'] . '.pages_wrap',
            ['pages' => $list, 'category_id' => $categoryPageId, 'pagination' => $paginationLinks, 'links' => $paginationLinks, 'total' => $total, 'content' => $options['content'], 'search_query' => $options['search_query']]
        );
    }

    /**
     * @param string $blockName
     * @return string|false
     */
    protected static function _getCustomBlockData($blockName)
    {
        if (isset(self::$_customBlockDataKey) && isset(self::$_customBlockData[self::$_customBlockDataKey][$blockName])) {
            return self::$_customBlockData[self::$_customBlockDataKey][$blockName];
        }
        return Repeater::load_repeater_data($blockName);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'block_') === 0) {
            return forward_static_call_array(['self', 'block'], $arguments);
        }
        return 'invalid function';
    }

}
