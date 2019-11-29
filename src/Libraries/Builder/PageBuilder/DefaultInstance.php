<?php namespace CoasterCms\Libraries\Builder\PageBuilder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\PageBuilderLogger;
use CoasterCms\Libraries\Builder\ViewClasses\BreadCrumb;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
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
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Request;
use URL;
use View;

class DefaultInstance
{

    use Macroable {
        __call as macroCall;
    }

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
     * @var bool
     */
    public $cacheable;

    /**
     * @var PageBuilderLogger
     */
    protected $_logger;

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
    public $customBlockDataKey;

    /**
     * @param PageBuilderLogger $logger
     * @param PageLoader $pageLoader
     */
    public function __construct(PageBuilderLogger $logger, PageLoader $pageLoader)
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
        $this->cacheable = true;

        $this->_customBlockData = [];
        $this->customBlockDataKey = 0;

        $this->_logger = $logger;
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
            $templatePath = $themePath . 'feed.' . $this->feedExtension . '.' . $this->template;

            if (!view()->exists($templatePath)) {
                $templatePath = $themePath . 'feed.' . $this->feedExtension . '.default';
            }
            return $templatePath;
        } else {
            return $themePath . 'templates.' . $this->template;
        }
    }

    /**
     * @param string $customTemplate
     * @param array $viewData
     * @return string
     */
    public function templateRender($customTemplate = null, $viewData = [])
    {
        if (isset($customTemplate)) {
            $this->customTemplate = $customTemplate;
        }
        return view($this->templatePath(), $viewData)->render();
    }

    /**
     * @param int|null $setValue
     * @return bool
     */
    public function canCache($setValue = null)
    {
        if (!is_null($setValue)) {
            $this->cacheable = (bool) $setValue;
        }
        if ($this->_logger->logs('method')->contains('search')) {
            return false;
        }
        return $this->cacheable;
    }

    /**
     * @param bool $noOverride
     * @return string
     */
    public function pageJson($noOverride = false)
    {
        $page = $this->_getPage($noOverride);
        return $page->toJson();
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
     * Get parent page id based on the page levels loaded from url
     * @param bool $noOverride
     * @return int
     */
    public function parentPageId($noOverride = false)
    {
        if ($this->pageOverride && !$noOverride) {
            return $this->pageOverride->parent;
        } else {
            return ($parentIndex = (count($this->pageLevels) - 2)) >= 0 ? $this->pageLevels[$parentIndex]->id : 0;
        }
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
        $key = is_null($key) ? $this->customBlockDataKey : $key;
        if (empty($this->_customBlockData[$key])) {
            $this->_customBlockData[$key] = [];
        }
        if ($overwrite || !array_key_exists($blockName, $this->_customBlockData[$key])) {
            $this->_customBlockData[$key][$blockName] = $content;
        }
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
            '404-name' => ''
        ];
        $options = array_merge($defaultOptions, $options);

        $pageLevels = $this->pageLevels;

        if ($options['404-name'] !== '') {
            end($pageLevels);
            current($pageLevels)->pageCurrentLang->name = $options['404-name'];
        }

        $crumbs = [];
        if (!empty($pageLevels)) {
            $url = '';
            end($pageLevels);
            $lowestLevel = key($pageLevels);
            foreach ($pageLevels as $level => $page) {

                if ($page) {
                    $url .= rtrim('/' . $page->pageLang()->url, '/');
                }
                $active = ($lowestLevel == $level);

                $crumb = new BreadCrumb($page->pageLang(), $url ?: '/', $active);

                if ($this->_viewExists('.breadcrumbs.' . $options['view'] . '.active_element') && $active) {
                    $crumbs[] = $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.active_element', ['crumb' => $crumb]);
                } else {
                    $crumbs[] = $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.link_element', ['crumb' => $crumb]);
                }
            }
        }
        $crumbsHtml = implode($this->_getRenderedView('breadcrumbs.' . $options['view'] . '.separator'), $crumbs);
        return $this->_getRenderedView('breadcrumbs.' . $options['view'] . '.wrap', ['crumbs' => $crumbsHtml]);
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
        $topLevelPages = Page::where('parent', '=', 0)->orderBy('order', 'asc')->get();
        $topLevelPages = $topLevelPages->isEmpty() ? [] : $topLevelPages;
        foreach ($topLevelPages as $key => $page) {
            if (!$page->is_live() || !$page->sitemap) {
                unset($topLevelPages[$key]);
            }
        }
        return $this->_renderCategory(0, $topLevelPages, $options);
    }

    /**
     * @param int $categoryPageId
     * @param array|null $pages
     * @param array $options
     * @return string
     */
    public function pages($categoryPageId = null, $pages = null, $options = [])
    {
        $pages = is_null($pages) ? Page::all() : $pages;
        return $this->_renderCategory($categoryPageId, $pages, $options);
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
            $options += ['renderIfEmpty' => false];
            if (!empty($options['sitemap'])) {
                foreach ($pages as $key => $page) {
                    if (!$page->sitemap) {
                        unset($pages[$key]);
                    }
                }
            }
            return $this->_renderCategory($pageId, $pages, $options);
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
                $pages = array_values($pages); // so key +/- goes forth/back by index (not page_id)
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
                    if (array_key_exists($liveBlock->page_id, $filteredPagesForBlock)) {
                        continue; // skip unnecessary extra checks
                    }
                    if ($blockTypeObject->filter($liveBlock->content, $searchValue, $options['match'])) {
                        $filteredPagesForBlock[$liveBlock->page_id] = Page::preload($liveBlock->page_id);
                    }
                }
                if ($options['operand'] == 'OR' || $k == 0) {
                    $filteredPages = $filteredPages + $filteredPagesForBlock;
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
        $pages = [];
        if ($this->searchQuery !== false) {
            $pages = PageSearchData::lookup($this->searchQuery);
            if (!empty($pages)) {
                // pass to renderer as it will do filtering on pages
                $pages = $this->_renderCategory(0, $pages, ['render' => false] + $options);
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
     * @param string $fn
     * @return mixed
     */
    protected function _block($blockName, $options = [], $fn = 'display')
    {
        // force query available if block details changed in current request
        $block = Block::preloadClone($blockName, isset($options['force_query']));
        $pageId = isset($options['page_id']) ? Path::unParsePageId($options['page_id']) : $this->pageId();

        $usingGlobalContent = false;
        $blockData = null;

        if (($customBlockData = $this->_getCustomBlockData($blockName)) !== null && !isset($options['page_id'])) {
            // load custom block data for (is also used for repeater content)
            $blockData = $customBlockData;
        } elseif ($block->exists) {

            // load block data
            $globalBlockData = PageBlockDefault::preload($block->id);
            $pageBlockData = PageBlock::preloadPageBlock($pageId, $block->id, $this->pageVersion($pageId));

            // get languages
            $loadForLanguages = [!empty($options['language']) ? $options['language'] : Language::current()];
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

        } else {
            return config('app.env') == 'development' ? 'block not found' : '';
        }

        // return raw data
        if (isset($options['raw']) && $options['raw']) {
            return $blockData;
        }

        // set version that data has been grabbed for (0 = latest)
        if(empty($options['version'])) {
            $options['version'] = $usingGlobalContent ? 0 : $this->pageVersion($pageId);
        }

        // generate type object (ie. String / Image / Repeater) with page and version data
        $blockTypeObject = $block->setPageId($pageId)->setVersionId($options['version'])->getTypeObject();

        // return rendered view or run custom function on block type
        return $blockTypeObject->$fn($blockData, $options);
    }

    /**
     * Return string or rendered view for block
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function block($blockName, $options = [])
    {
        return $this->_block($blockName, $options);
    }

    /**
     * Return data for block
     * @param string $blockName
     * @param array $options
     * @return mixed
     */
    public function blockData($blockName, $options = [])
    {
        return $this->_block($blockName, $options, 'data');
    }

    /**
     * Return data for block as json
     * @param string $blockName
     * @param array $options
     * @return string json
     */
    public function blockJson($blockName, $options = [])
    {
        if (array_key_exists('returnAll', $options) && $options['returnAll']) {
            unset($options['returnAll']);
            $blocksData = [];
            $themeId = ($theme = Theme::where('theme', '=', $this->theme)->first()) ? $theme->id : 0;
            $template = Template::preload($this->template);
            $categoryBlocks = ThemeTemplate::templateBlocks($themeId, $template->exists ? $template->id : null);
            foreach ($categoryBlocks as $blockCategory => $blocks) {
                foreach ($blocks as $block) {
                    $blocksData += json_decode($this->blockJson($block->name, $options), true);
                }
            }
            return collect($blocksData)->toJson();
        }
        return $this->_block($blockName, $options, 'toJson');
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
     * @return string|array
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
            'per_page' => 0,
            'limit' => 0,
            'content' => '',
            'templates' => [],
            'groups' => [],
            'fromPageIds' => [],
            'canonicals' => config('coaster::frontend.canonicals')
        ];
        $options = array_merge($defaultOptions, $options);

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
        $pages = $pagesOfSelectedType;

        // filtering by templates/groups/pageIds
        if (!empty($options['templates'])) {
            $templates = Template::getTemplateIds($options['templates']); // converts names to ids
            foreach ($pages as $k => $page) {
                if (!isset($page->template) || !in_array($page->template, $templates)) {
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
        if (!empty($options['fromPageIds'])) {
            // keep order of fromPageIds
            $pagesById = [];
            foreach ($pages as $k => $page) {
                $pagesById[$page->id] = $page;
            }
            $pages = [];
            foreach ($options['fromPageIds'] as $pageId) {
                if (array_key_exists($pageId, $pagesById)) {
                    $pages[] = $pagesById[$pageId];
                }
            }
        }

        // limit results
        if (!empty($options['limit']) && is_int($options['limit'])) {
            $pages = array_slice($pages, 0, $options['limit']);
        }

        if (!$options['render']) {
            return $pages;
        }

        // pagination
        if (!empty($options['per_page']) && (int)$options['per_page'] > 0) {
            $paginator = new LengthAwarePaginator($pages, count($pages), $options['per_page'], Request::input('page', 1));
            $paginator->setPath(Request::getPathInfo());
            $paginator->appends(Request::all());
            $paginationLinks = PaginatorRender::run($paginator);
            $pages = array_slice($pages, (($paginator->currentPage() - 1) * $options['per_page']), $options['per_page']);
        } else {
            $paginationLinks = '';
        }

        $list = '';
        $total = count($pages);

        if (!$total && !$options['renderIfEmpty']) {
            return '';
        }

        $categoryPageId = is_numeric($categoryPageId) ? $categoryPageId : 0;
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
                $tmpCustomBlockKey = $this->customBlockDataKey;
                $this->customBlockDataKey = 'customPage:'.$page->id;
                $pageDetails = new \stdClass;
                foreach ($page as $blockName => $content) {
                    if (in_array($blockName, ['fullUrl', 'fullName'])) {
                        $pageDetails->$blockName = $content;
                    } else {
                        $this->setCustomBlockData($blockName, $content, $this->customBlockDataKey);
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
                $this->customBlockDataKey = $tmpCustomBlockKey;
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
        if (array_key_exists($this->customBlockDataKey, $this->_customBlockData) && array_key_exists($blockName, $this->_customBlockData[$this->customBlockDataKey])) {
            return $this->_customBlockData[$this->customBlockDataKey][$blockName];
        } else {
            return null;
        }
    }

    /**
     * @param string $varName
     * @return mixed
     */
    public function getData($varName = '')
    {
        $varName = camel_case($varName);
        if ($varName) {
            return property_exists($this, $varName) ? $this->$varName : null;
        } else {
            return get_object_vars($this);
        }
    }

    /**
     * @param string $varName
     * @param mixed $value
     */
    public function setData($varName, $value)
    {
        if ($varName && property_exists($this, $varName)) {
            $this->$varName = $value;
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
