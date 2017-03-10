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
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Setting;
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
     * @var string
     */
    public $contentType;

    /**
     * @var bool
     */
    public $is404;

    /**
     * @var bool
     */
    public $isLive;

    /**
     * @var PageVersion|null
     */
    public $previewVersion;

    /**
     * @var string|false
     */
    public $customTemplate;

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
    protected $_customBlockDataKey;

    /**
     * @param PageLoader $pageLoader
     */
    public function __construct(PageLoader $pageLoader)
    {
        $this->page = !empty($pageLoader->pageLevels) ? end($pageLoader->pageLevels) : null;
        $this->pageLevels = $pageLoader->pageLevels;

        $this->is404 = $pageLoader->is404;
        $this->isLive = $pageLoader->isLive;
        $this->previewVersion = $pageLoader->previewVersion;
        $this->customTemplate = $pageLoader->customTemplate;
        $this->feedExtension = $pageLoader->feedExtension;
        $this->searchQuery = $pageLoader->searchQuery;

        $this->theme = $pageLoader->theme;
        $this->template = $pageLoader->template;
        $this->contentType = $pageLoader->contentType;

        $this->_customBlockData = [];
        $this->_customBlockDataKey = 0;
    }

    /**
     * @return string
     */
    public function themePath()
    {
        return 'themes.' . $this->theme . '.';
    }

    /**
     * @param bool $withThemePath
     * @return string
     */
    public function templatePath($withThemePath = true)
    {
        $themePath = $withThemePath ? $this->themePath() : '';
        if ($this->customTemplate) {
            return $themePath . $this->customTemplate;
        } elseif ($this->feedExtension) {
            return $themePath . 'feed.' . $this->feedExtension . '.' . $this->template;
        } else {
            return $themePath . 'templates.' . $this->template;
        }
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
        return (!empty($page) && $page->pageLang()) ? $page->pageLang()->live_version : 0;
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrlSegment($pageId = 0, $noOverride = false)
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        return $pageId ? PageLang::getUrl($pageId): '';
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
        return $pageId ? PageLang::getName($pageId): '';
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
     * @param int $pageId
     * @param bool $noOverride
     * @return int
     */
    public function pageVersion($pageId = 0, $noOverride = false)
    {
        $pageId = $pageId ?: $this->pageId($noOverride);
        if ($this->previewVersion && $pageId == $this->pageId(true)) {
            return $this->previewVersion->version_id;
        } else {
            return PageLang::preload($pageId)->live_version;
        }
    }

    /**
     * @param string $fileName
     * @param array $options
     * @return string
     */
    public function img($fileName, $options = [])
    {
        $imageData = new \stdClass;
        $imageData->file =  $fileName;
        if (empty($options['full_path'])) {
            $imageData->file = '/themes/' . $this->theme . '/img/' . $imageData->file;
        }
        $imageBlock = (new Block);
        $imageBlock->type = 'image';
        return $imageBlock->getTypeObject()->display(serialize($imageData), $options);
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
     * @param bool $overwrite
     */
    public function setCustomBlockData($blockName, $content, $key = null, $overwrite = true)
    {
        $key = is_null($key) ? $this->_customBlockDataKey : $key;
        if (empty($this->_customBlockData[$key])) {
            $this->_customBlockData[$key] = [];
        }
        if ($overwrite || !array_key_exists($blockName, $this->_customBlockData[$key])) {
            $this->_customBlockData[$key][$blockName] = $content;
        }
    }

    /**
     * @param string|int $key
     */
    public function setCustomBlockDataKey($key)
    {
        $this->_customBlockDataKey = $key;
    }

    /**
     * @return string|int
     */
    public function getCustomBlockDataKey()
    {
        return $this->_customBlockDataKey;
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
     * @param array $viewData
     * @return string
     */
    public function section($section, $viewData = [])
    {
        return $this->_getRenderedView('sections.' . $section, $viewData);
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
            $page404->setRelation('pageCurrentLang', $pageLang);
            $pageLevels[] = $page404;
        }

        $crumbs = '';
        if (!empty($pageLevels)) {
            $url = '';
            end($pageLevels);
            $lowestLevel = key($pageLevels);
            foreach ($pageLevels as $level => $page) {

                if ($page && $page->pageLang()->url != '/') {
                    $url .= '/' . $page->pageLang()->url;
                }
                $active = ($lowestLevel == $level);

                $crumb = new BreadCrumb($page->pageLang(), $url, $active);

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
     * @param string|array $blockName
     * @param string|array $search
     * @param array $options
     * @return string
     */
    public function filter($blockName, $search, $options = [])
    {
        $defaultOptions = [
            'match' => '=',
            'fromPageIds' => [],
            'operand' => 'AND',
            'multiFilter' => false
        ];
        $options = array_merge($defaultOptions, $options);
        $pageId = !empty($options['page_id']) ? $options['page_id'] : $this->pageId();
        $blockNames = is_array($blockName) ? $blockName : [$blockName];
        $searches = $options['multiFilter'] ? $search : [$search];
        $filteredPages = [];
        foreach ($blockNames as $k => $blockName) {
            $block = Block::preload($blockName);
            if ($block->exists) {
                $blockTypeObject = $block->getTypeObject();
                $searchValue = $searches[count($searches) > 1 ? $k : 0];
                $filteredPagesForBlock = [];
                $liveBlocks = PageBlock::livePageBlocksForBlock($block->id);
                foreach ($liveBlocks as $liveBlock) {
                    if (array_key_exists($liveBlock->page_id, $filteredPagesForBlock) || (!empty($options['fromPageIds']) && !in_array($liveBlock->page_id, $options['fromPageIds']))) {
                        continue;
                    }
                    if ($blockTypeObject->filter($liveBlock->content, $searchValue, $options['match'])) {
                        $filteredPagesForBlock[$liveBlock->page_id] = Page::preload($liveBlock->page_id);
                    }
                }
                if ($options['operand'] == 'OR' || $k == 0) {
                    $filteredPages = array_merge($filteredPages, $filteredPagesForBlock);
                } else {
                    $filteredPages = array_intersect_key($filteredPages, $filteredPagesForBlock);
                }
            }
        }
        return $this->_renderCategory($pageId, $filteredPages, $options);
    }

    /**
     * @param array $blockNames
     * @param array $searches
     * @param array $options
     * @return string
     */
    public function filters($blockNames, $searches, $options = [])
    {
        $options['multiFilter'] = true;
        return $this->categoryFilter($blockNames, $searches, $options);
    }

    /**
     * @param string|array $blockName
     * @param string|array $search
     * @param array $options
     * @return string
     */
    public function categoryFilter($blockName, $search, $options = [])
    {
        $pageId = !empty($options['page_id']) ? $options['page_id'] : $this->pageId();
        if ($pageId) {
            $options['fromPageIds'] = [];
            $categoryPages = Page::category_pages($pageId, true);
            foreach ($categoryPages as $categoryPage) {
                $options['fromPageIds'][] = $categoryPage->id;
            }
            return $this->filter($blockName, $search, $options);
        }
        return '';
    }

    /**
     * @param array $blockNames
     * @param array $searches
     * @param array $options
     * @return string
     */
    public function categoryFilters($blockNames, $searches, $options = [])
    {
        $options['multiFilter'] = true;
        return $this->categoryFilter($blockNames, $searches, $options);
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
                if (!empty($options['groups'])) {
                    $pageIds = [];
                    $pageGroupPages = PageGroupPage::whereIn('group_id', $options['groups'])->get();
                    foreach ($pageGroupPages as $pageGroupPage) {
                        $pageIds[] = $pageGroupPage->page_id;
                    }
                    foreach ($pages as $k => $page) {
                        if (!in_array($page->id, $pageIds)) {
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
        $block = Block::preloadClone($blockName, isset($options['force_query']));
        $pageId = isset($options['page_id']) ? Path::unParsePageId($options['page_id']) : $this->pageId();

        $usingGlobalContent = false;
        $blockData = null;

        if (($customBlockData = $this->_getCustomBlockData($blockName)) !== null) {
            // load custom block data for (is also used for repeater content)
            $blockData = $customBlockData;
        } elseif ($block->exists) {

            // load block data
            $globalBlockData = PageBlockDefault::preload($block->id);
            $pageBlockData = PageBlock::preloadPageBlock($pageId, $block->id, $this->pageVersion($pageId));

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
            $options['version'] = $usingGlobalContent ? 0 : $this->pageVersion($pageId);
        }

        // pass block details and data to display class
        return $block->setPageId($pageId)->setVersionId($options['version'])->getTypeObject()->display($blockData, $options);
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

    /**
     * @param bool $noOverride
     * @return Page
     */
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
     * @param Page[]|Collection $pages
     * @param array $options
     * @return string
     */
    protected function _renderCategory($categoryPageId, $pages, $options)
    {
        if (array_key_exists('view', $options) && empty($options['view'])) {
            unset($options['view']);
        }
        $defaultOptions = [
            'render' => true,
            'renderIfEmpty' => true,
            'view' => 'default',
            'type' => 'all',
            'per_page' => 20,
            'limit' => 0,
            'content' => '',
            'canonicals' => config('coaster::frontend.canonicals')
        ];
        $options = array_merge($defaultOptions, $options);

        if (!$options['render']) {
            return $pages;
        }

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
            $paginator->appends(Request::all());
            $paginationLinks = PaginatorRender::run($paginator);
            $pages = array_slice($pagesOfSelectedType, (($paginator->currentPage() - 1) * $options['per_page']), $options['per_page']);
        } else {
            $pages = $pagesOfSelectedType;
            $paginationLinks = '';
        }

        $list = '';
        $total = count($pages);

        if (!$total && !$options['renderIfEmpty']) {
            return '';
        }

        $groupPageContainerId = 0;
        if ($categoryPageId && !$options['canonicals']) {
            $categoryPage = Page::preload($categoryPageId);
            $groupPageContainerId = ($categoryPage->exists && $categoryPage->group_container > 0) ? $categoryPage->id : 0;
        }

        $pages = array_values($pages);
        foreach ($pages as $count => $page) {
            $isFirst = ($count == 0);
            $isLast = ($count == $total -1);

            if (is_string($page->id)) {
                $tmpCustomBlockKey = $this->_customBlockDataKey;
                $this->_customBlockDataKey = 'customPage:'.$page->id;
                $pageDetails = new \stdClass;
                foreach ($page as $blockName => $content) {
                    if (in_array($blockName, ['fullUrl', 'fullName'])) {
                        $pageDetails->$blockName = $content;
                    } else {
                        $this->setCustomBlockData($blockName, $content, $this->_customBlockDataKey);
                    }
                }
                Path::addCustomPagePath($page->id, $pageDetails);
            }

            $fullPageInfo = new PageDetails($page->id, $groupPageContainerId);

            $tmp = $this->pageOverride;
            $this->pageOverride = $page;

            $list .= $this->_getRenderedView(
                'categories.' . $options['view'] . '.page',
                ['page' => $fullPageInfo, 'category_id' => $categoryPageId, 'is_first' => $isFirst, 'is_last' => $isLast, 'count' => $count + 1, 'total' => $total]
            );

            if (isset($tmpCustomBlockKey)) {
                $this->_customBlockDataKey = $tmpCustomBlockKey;
                $tmpCustomBlockKey = null;
            }

            $this->pageOverride = $tmp;
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
        if (isset($this->_customBlockDataKey) && !empty($this->_customBlockData[$this->_customBlockDataKey]) && array_key_exists($blockName, $this->_customBlockData[$this->_customBlockDataKey])) {
            return $this->_customBlockData[$this->_customBlockDataKey][$blockName];
        } else {
            return null;
        }
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
