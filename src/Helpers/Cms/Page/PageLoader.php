<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use Illuminate\Database\Eloquent\Builder;
use Request;

class PageLoader
{
    /**
     * @var array
     */
    public $pageLevels;

    /**
     * @var bool
     */
    public $is404;

    /**
     * @var bool
     */
    public $isLive;

    /**
     * @var null|PageVersion
     */
    public $previewVersion;

    /**
     * @var false|string
     */
    public $customTemplate;

    /**
     * @var false|string
     */
    public $feedExtension;

    /**
     * @var false|string
     */
    public $searchQuery;

    /**
     * @var string
     */
    public $theme;

    /**
     * @var string
     */
    public $template;

    /**
     * @var string
     */
    public $contentType;

    /**
     * PageLoader constructor.
     */
    public function __construct()
    {
        $this->pageLevels = [];
        $this->is404 = true;
        $this->isLive = true;
        $this->previewVersion = false;
        $this->customTemplate = false;
        $this->feedExtension = false;
        $this->searchQuery = false;
        $this->_load();
    }

    /**
     *
     */
    protected function _load()
    {
        $this->_loadPageLevels();
        $this->_loadPageStatus();
        $this->_loadPageTemplate();
    }

    /**
     * Load all page levels for current request.
     * Also check if search page or feed page.
     */
    protected function _loadPageLevels()
    {
        $urlSegments = count(Request::segments());

        // check for homepage feed
        if ($urlSegments == 1 && substr(Request::segment(1), 0, 5) == 'root.') {
            if ($this->feedExtension = Feed::getFeedExtensionFromPath(Request::segment(1))) {
                $urlSegments = 0;
            }
        }

        // load homepage
        if (empty($this->pageLevels[0])) {
            $this->pageLevels[0] = self::_loadHomePage();
        }

        // load sub pages
        if ($urlSegments) {
            for ($i = 1; $i <= $urlSegments; $i++) {
                if (empty($this->pageLevels[$i])) {

                    $currentSegment = Request::segment($i);

                    if ($urlSegments == $i && $this->feedExtension = Feed::getFeedExtensionFromPath($currentSegment)) {
                        Feed::removeFeedExtensionFromPath($currentSegment);
                    }

                    $parentPage = $this->pageLevels[$i - 1];
                    if ($i == 1) {
                        $parentPage = isset($parentPage) ? clone $parentPage : new Page;
                        $parentPage->id = 0;
                    }

                    $this->pageLevels[$i] = self::_loadSubPage($currentSegment, $parentPage);

                    if (empty($this->pageLevels[$i])) {
                        if (($searchOffset = self::_isSearchPage($currentSegment, $parentPage)) !== false) {
                            Search::setSearchBlockRequired();
                            $this->searchQuery = implode('/', array_slice(Request::segments(), $i - 1 + $searchOffset));
                            unset($this->pageLevels[$i]);
                            $urlSegments = $i - 1;
                        }
                        break;
                    }

                }
            }
        }

        if (!empty($this->pageLevels[$urlSegments])) {
            $this->is404 = false;
        }

        if ($this->searchQuery === false && $query = Request::input('q')) {
            $this->searchQuery = $query;
        }
        $this->searchQuery = $this->searchQuery !== false ? urldecode($this->searchQuery) : false;

        $this->pageLevels = array_filter($this->pageLevels);

    }

    /**
     * Load current page status.
     * Is live, Is preview, Custom Template
     */
    protected function _loadPageStatus()
    {
        $lowestLevelPage = count($this->pageLevels) > 0 ? end($this->pageLevels) : null;

        if ($lowestLevelPage) {

            /** @var Page $lowestLevelPage */
            $this->isLive = $lowestLevelPage->is_live();

            if (!$this->is404) {
                if ($previewKey = Request::input('preview')) {
                    $this->previewVersion = PageVersion::where('page_id', '=', $lowestLevelPage->id)->where('preview_key', '=', $previewKey)->first() ?: null;
                }
            }

        }

        if($customTemplate = Request::get('external')) {
            $this->customTemplate = 'externals.' . $customTemplate;
        }

    }

    /**
     * Load theme name, template name and content type to return
     */
    public function _loadPageTemplate()
    {
        $theme = Theme::find(config('coaster::frontend.theme'));
        $lowestLevelPage = count($this->pageLevels) > 0 ? end($this->pageLevels) : null;

        $this->theme = !empty($theme) && is_dir(base_path('/resources/views/themes/' . $theme->theme)) ? $theme->theme : 'default';
        $this->template = $lowestLevelPage ? Template::name($this->previewVersion ? $this->previewVersion->template : $lowestLevelPage->template) : '';
        $this->contentType = $this->feedExtension ? Feed::getMimeType($this->feedExtension) : 'text/html; charset=UTF-8';
    }

    /**
     * @return Page|null
     */
    protected function _loadHomePage()
    {
        $paths = ['', '/'];
        return self::_pageQuery($paths, 0);
    }

    /**
     * @param string $path
     * @param Page $parentPage
     * @return Page|null
     */
    protected function _loadSubPage($path, Page $parentPage)
    {
        $paths = [$path];
        $page = self::_pageQuery($paths, $parentPage->id);

        if (!$page && $parentPage->group_container > 0) {
            $page = self::_pageQuery($paths, false, $parentPage->group_container);
            if ($page) {
                $group = PageGroup::preload($parentPage->group_container);
                $page = in_array($page->id, $group->itemPageIdsFiltered($parentPage->id)) ? $page : null;
            }
        }

        return $page;
    }

    /**
     * @param array $paths
     * @param bool|int $byParentId
     * @param bool|int $byGroupContainerId
     * @return Page|null
     */
    protected function _pageQuery($paths, $byParentId = false, $byGroupContainerId = false)
    {
        /** @var Builder $pageQuery */
        $pageQuery = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->whereIn('page_lang.url', $paths);

        if ($byParentId !== false) {
            $pageQuery->where('parent', '=', $byParentId);
        }

        if ($byGroupContainerId !== false) {
            $pageQuery->join('page_group_pages', 'page_group_pages.page_id', '=', 'pages.id')->where('page_group_pages.group_id', '=', $byGroupContainerId);
        }

        /** @var Page $page */
        $page = $pageQuery->first(['pages.*']);
        if (!empty($page) && $page->pageLang()) {
            return $page;
        } else {
            return null;
        }
    }

    /**
     * If a search page returns an additional offset from which segment to take the search query
     * @param string $path
     * @param Page $parentPage
     * @return false|int
     */
    protected function _isSearchPage($path, Page $parentPage)
    {
        $ppIsNamedSearch = ($parentPage->pageLang() && $parentPage->pageLang()->url == 'search');
        if ($path == 'search' || $ppIsNamedSearch) {
            return $ppIsNamedSearch ? 0 : 1;
        } else {
            return false;
        }
    }

}