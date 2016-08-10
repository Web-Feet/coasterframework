<?php namespace CoasterCms\Libraries\Builder\PageBuilder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Helpers\Cms\Page\Search;
use CoasterCms\Libraries\Builder\ViewClasses\BreadCrumb;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Libraries\Blocks\Image;
use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Libraries\Builder\MenuBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
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
use Illuminate\Support\Str;
use Request;
use URL;
use View;

class PageBuilderInstance
{

    /**
     * @var Page
     */
    public $pageOverride;

    /**
     * @var Page
     */
    public $page;

    /**
     * @var Page[]
     */
    public $pageLevels;

    /**
     * @var string
     */
    public $template;

    /**
     * @var string
     */
    public $theme;

    /**
     * @var bool
     */
    public $is404;

    /**
     * @var bool
     */
    public $isPreview;

    /**
     * @var bool
     */
    public $isLive;

    /**
     * @var string|false
     */
    public $externalTemplate;

    /**
     * @var string|false
     */
    public $feedExtension;

    /**
     * @var string|false
     */
    public $searchQuery;

    /**
     * @var array
     */
    protected $_pageCategoryLinks;

    /**
     * @var array
     */
    protected $_customBlockData;

    /**
     * @var int
     */
    protected static $_customBlockDataKey;

    /**
     * @param PageLoader $pageLoader
     */
    public function __construct(PageLoader $pageLoader)
    {
        $this->page = !empty($pageLoader->pageLevels) ? end($pageLoader->pageLevels) : null;
        $this->pageLevels = $pageLoader->pageLevels;

        $this->template = $this->page ? Template::name($this->page->template) : '';

        $this->is404 = $pageLoader->is404;
        $this->isPreview = $pageLoader->isPreview;
        $this->isLive = $pageLoader->isLive;
        $this->externalTemplate = $pageLoader->externalTemplate;
        $this->feedExtension = $pageLoader->feedExtension;
        $this->searchQuery = $pageLoader->searchQuery;
    }

    /**
     * @param int $themeId
     */
    public function setTheme($themeId)
    {
        $theme = Theme::find($themeId);
        if (!empty($theme) && is_dir(base_path('/resources/views/themes/' . $theme->theme))) {
            $this->theme = $theme->theme;
        } else {
            $this->theme = 'default';
        }
    }

    /**
     * @param int|string $template
     */
    public function setTemplate($template)
    {
        $this->template = is_numeric($template) ? Template::name($template) : $template;
    }
    
    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageId($noOverride = false)
    {
        $page = $this->_getPage($noOverride);
        return !empty($page) ? $page->id : 0;
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function parentPageId($noOverride = false)
    {
        $page = $this->_getPage($noOverride);
        return !empty($page) ? $page->parent : 0;
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageTemplateId($noOverride = false)
    {
        $page = $this->_getPage($noOverride);
        return !empty($page) ? $page->template : 0;
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageLiveVersionId($noOverride = false)
    {
        $page = $this->_getPage($noOverride);
        return (!empty($page) && !$page->page_lang->isEmpty()) ? $page->page_lang[0]->live_version : 0;
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrlSegment($pageId = 0, $noOverride = false)
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        return $pageId ? PageLang::url($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrl($pageId = 0, $noOverride = false)
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        return $pageId ? Path::getFullUrl($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageName($pageId = 0, $noOverride = false)
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        return $pageId ? PageLang::name($pageId): '';
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @param string $sep
     * @return string
     */
    public function pageFullName($pageId = 0, $noOverride = false, $sep = ' &raquo; ')
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        return $pageId ? Path::getFullName($pageId, $sep): '';
    }

    /**
     * @param string $fileName
     * @param array $options
     * @return string
     */
    public function img($fileName, $options = [])
    {
        $image_data = new \stdClass;
        $image_data->file = '/themes/' . $this->theme . '/img/' . $fileName;
        return Image::display(null, $image_data, $options);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function css($fileName)
    {
        return URL::to('/themes/' . $this->theme . '/css/' . $fileName . '.css');
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function js($fileName)
    {
        return URL::to('/themes/' . $this->theme . '/js/' . $fileName . '.js');
    }

    /**
     * @param string $blockName
     * @param mixed $content
     * @param int $key
     */
    public function setCustomBlockData($blockName, $content, $key = 0)
    {
        if (!isset($this->_customBlockData)) {
            $this->_customBlockData = [];
            $this->_customBlockDataKey = 0;
        }
        if (!isset($this->_customBlockData[$key])) {
            $this->_customBlockData[$key] = [];
        }
        $this->_customBlockData[$key][$blockName] = $content;
    }

    /**
     * @param string|int $key
     */
    public function setCustomBlockDataKey($key)
    {
        $this->_customBlockDataKey = $key;
    }

    /**
     * @param string $section
     * @return string
     */
    public function external($section)
    {
        return $this->_getRenderedView('externals.' . $section);
    }

    /**
     * @param string $section
     * @return string
     */
    public function section($section)
    {
        return $this->_getRenderedView('sections.' . $section);
    }

    /**
     * @param array $options
     * @return string
     */
    public function breadcrumb($options = [])
    {
        $defaultOptions = [
            'view' => 'default',
            '404-name' => '404'
        ];
        $options = array_merge($defaultOptions, $options);

        $pageLevels = $this->pageLevels;

        if ($this->is404) {
            $page404 = new Page;
            $pageLang = new PageLang;
            $pageLang->url = '';
            $pageLang->name = $options['404-name'];
            $page404->setRelation('page_lang', [$pageLang]);
            $pageLevels[] = $page404;
        }

        $crumbs = '';
        if (!empty($pageLevels)) {
            $url = '';
            end($pageLevels);
            $lowestLevel = key($pageLevels);
            foreach ($pageLevels as $level => $page) {

                if ($page && $page->page_lang[0]->url != '/') {
                    $url .= '/' . $page->page_lang[0]->url;
                }
                $active = ($lowestLevel == $level);

                $crumb = new BreadCrumb($page->page_lang[0], $url, $active);

                if ($this->_viewExists('.breadcrumbs.' . $options['view'] . '.active_element') && $active) {
                    $crumbs .= $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.active_element', ['crumb' => $crumb]);
                } else {
                    $crumbs .= $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.link_element', ['crumb' => $crumb]);
                    $crumbs .= $active ? $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.separator') : '';
                }
            }
        }
        return $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.wrap', ['crumbs' => $crumbs]);
    }

    /**
     * @param $menuName
     * @param array $options
     * @return string
     */
    public function menu($menuName, $options = [])
    {
        return MenuBuilder::menu($menuName, $options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function sitemap($options = [])
    {
        $topLevelPages = Page::where('parent', '=', 0)->get();
        $topLevelPages = $topLevelPages->isEmpty() ? [] : $topLevelPages;
        foreach ($topLevelPages as $key => $page) {
            if (!$page->is_live() || !$page->sitemap) {
                unset($topLevelPages[$key]);
            }
        }
        return $this->_renderCategory(0, $topLevelPages, $options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function category($options = [])
    {
        $pageId = !empty($options['page_id']) ? $options['page_id'] : $this->pageId();
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
                return $this->_renderCategory($pageId, $pages, $options);
            }
        }
        return '';
    }

    /**
     * @param string $direction
     * @return string
     */
    public function categoryLink($direction = 'next')
    {
        if (!isset($this->_pageCategoryLinks)) {
            $this->_pageCategoryLinks = [
                'next' => '',
                'prev' => ''
            ];
            $parentPageId = $this->parentPageId();
            if ($parentPageId) {
                $pages = Page::category_pages($parentPageId, true);
                if (count($pages) > 1) {
                    foreach ($pages as $k => $page) {
                        if ($page->id == $this->page->id) {
                            $key = $k;
                            break;
                        }
                    }
                    if (isset($key)) {
                        if (!empty($pages[$key + 1])) {
                            $this->_pageCategoryLinks['next'] = Path::getFullUrl($pages[$key + 1]->id);
                        }
                        if (!empty($pages[$key - 1])) {
                            $this->_pageCategoryLinks['prev'] = Path::getFullUrl($pages[$key - 1]->id);
                        }
                    }
                }
            }
        }
        return $this->_pageCategoryLinks[$direction];
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function filter($blockName, $search, $options = [])
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

        $pageId = !empty($options['page_id']) ? $options['page_id'] : $this->pageId();
        return $this->_renderCategory($pageId, $filteredPages, $options);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function categoryFilter($blockName, $search, $options = [])
    {
        $pageId = !empty($options['page_id']) ? $options['page_id'] : $this->pageId();
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
            return $this->_renderCategory($pageId, $filteredPages, $options);
        }
        return '';
    }

    /**
     * @param array $options
     * @return string
     */
    public function search($options = [])
    {
        Search::searchBlockFound();
        $pages = [];
        if ($this->searchQuery !== false) {
            $pages = PageSearchData::lookup($this->searchQuery);
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
                    $page = (int) Request::input('page');
                    $page = $page < 1 ? 1 : $page;
                    $max = (($page * 20) > $results) ? $results : ($page * 20);
                    $showing = " [showing " . (($page - 1) * 20 + 1) . " - " . $max . "]";
                }
                $options['content'] = "Search results for '" . $this->searchQuery . "' (" . $results . " match" . ((count($pages) > 1) ? 'es' : null) . " found)" . $showing . ":";
            } else {
                $options['content'] = "No results found for '" . $this->searchQuery . "'.";
            }
        } else {
            $options['content'] = array_key_exists('content', $options) ? $options['content'] : "No search query entered.";
        }

        return $this->_renderCategory(0, $pages, $options);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @return mixed|string
     */
    public function block($blockName, $options = [])
    {
        // force query available if block details changed in current request
        $block = Block::preload($blockName, isset($options['force_query']));

        $usingGlobalContent = false;
        $blockData = null;

        if (!empty($options['page_id'])) {
            $pageId = Path::unParsePageId($options['page_id']);
            $selectedPage = PageLang::preload($pageId);
            $pageVersionId = !empty($selectedPage) ? $selectedPage->live_version : 0;
        } else {
            $pageId = $this->pageId();
            $pageVersionId = $pageId ? $this->page->page_lang[0]->live_version : 0;
        }

        if (($customBlockData = $this->_getCustomBlockData($blockName)) !== false) {
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
                } elseif (!empty($globalBlockData[$language])) {
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
    public function blogPosts($getPosts = 3, $where = 'post_type = "post" AND post_status = "publish"')
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
    
    protected function _getPage($noOverride = false)
    {
        return ($this->pageOverride && !$noOverride) ? $this->pageOverride : $this->page;
    }

    /**
     * @param string $viewPath
     * @return bool
     */
    protected function _viewExists($viewPath)
    {
        $viewPath = 'themes.' . $this->theme . '.' . $viewPath;
        return View::exists($viewPath);
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @return string
     */
    protected function _getRenderedView($viewPath, $data = [])
    {
        $viewPath = 'themes.' . $this->theme . '.' . $viewPath;
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
    protected function _renderCategory($categoryPageId, $pages, $options)
    {
        $defaultOptions = [
            'view' => 'default',
            'type' => 'all',
            'per_page' => 20,
            'limit' => 0,
            'content' => '',
            'canonicals' => false
        ];
        $options = array_merge($defaultOptions, array_filter($options));

        // select page of selected type
        $pagesOfSelectedType = [];
        if ($options['type'] == 'all') {
            $pagesOfSelectedType = is_a($pages, Collection::class) ? $pages->all() : $pages;
        } else {
            foreach ($pages as $page) {
                $children = count(Page::getChildPageIds($page->id));
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
            $paginationLinks = '';
        }

        $list = '';
        $total = count($pages);

        $groupPageContainerId = 0;
        if ($categoryPageId && !$options['canonicals']) {
            $categoryPage = Page::preload($categoryPageId);
            $groupPageContainerId = ($categoryPage && $categoryPage->group_container > 0) ? $categoryPage->id : 0;
        }

        $pages = array_values($pages);
        foreach ($pages as $count => $page) {
            $isFirst = ($count == 0);
            $isLast = ($count == $total -1);
            
            $fullPageInfo = new PageDetails($page->id, $groupPageContainerId);

            $this->pageOverride = $page;

            $list .= $this->_getRenderedView(
                'categories.' . $options['view'] . '.page',
                ['page' => $fullPageInfo, 'category_id' => $categoryPageId, 'is_first' => $isFirst, 'is_last' => $isLast, 'count' => $count + 1, 'total' => $total]
            );

            $this->pageOverride = null;
        }

        return $this->_getRenderedView(
            'categories.' . $options['view'] . '.pages_wrap',
            ['pages' => $list, 'category_id' => $categoryPageId, 'pagination' => $paginationLinks, 'links' => $paginationLinks, 'total' => $total, 'content' => $options['content'], 'search_query' => $this->searchQuery]
        );
    }

    /**
     * @param string $blockName
     * @return string|false
     */
    protected function _getCustomBlockData($blockName)
    {
        if (isset($this->_customBlockDataKey) && isset($this->_customBlockData[$this->_customBlockDataKey][$blockName])) {
            return $this->_customBlockData[$this->_customBlockDataKey][$blockName];
        }
        return Repeater::load_repeater_data($blockName);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|string
     * @throws PageBuilderException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'block_') === 0) {
            return forward_static_call_array([$this, 'block'], $arguments);
        }
        $camelName = Str::camel($name);
        if (method_exists($this, $camelName)) {
            return forward_static_call_array([$this, $camelName], $arguments);
        }
        throw new PageBuilderException('function ' . $name . '() not found');
    }

}