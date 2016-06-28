<?php namespace CoasterCms\Helpers\Core\Page;

use CoasterCms\Models\Language;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\PageVersionSchedule;
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
    public $isPreview;

    /**
     * @var bool
     */
    public $isLive;

    /**
     * @var false|string
     */
    public $externalTemplate;

    /**
     * @var false|string
     */
    public $feedExtension;

    /**
     * @var false|string
     */
    public $searchQuery;

    /**
     * PageLoader constructor.
     */
    public function __construct()
    {
        $this->pageLevels = [];
        $this->is404 = true;
        $this->isPreview = false;
        $this->isLive = true;
        $this->externalTemplate = false;
        $this->feedExtension = false;
        $this->searchQuery = false;
        $this->_load();
    }

    /**
     *
     */
    protected function _load()
    {
        PageVersionSchedule::checkPageVersionIds();
        $this->_loadPageLevels();
        $this->_loadPageStatus();
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
            if ($this->feedExtension = Feed::getFeedExtension(Request::segment(1))) {
                $urlSegments = 0;
            }
        }

        // load site languages to find pages for
        $siteLanguages = [Language::current()];
        if (config('coaster::frontend.language_fallback') == 1) {
            $siteLanguages[] = config('coaster::frontend.language');
        }

        foreach ($siteLanguages as $languageId) {

            // load homepage
            if (empty($this->pageLevels[0])) {
                $this->pageLevels[0] = self::_loadHomePage($languageId);
            }

            // load subpages
            if (!empty($this->pageLevels[0]) && $urlSegments) {
                for ($i = 1; $i <= $urlSegments; $i++) {
                    if (empty($this->pageLevels[$i])) {

                        $currentSegment = Request::segment($i);
                        
                        if ($this->feedExtension = Feed::getFeedExtension($currentSegment)) {
                            $currentSegment = Feed::removeFeedExtension($currentSegment);
                        }

                        if ($i > 1) {
                            $parentPage = $this->pageLevels[$i - 1];
                        } else {
                            $parentPage = new Page;
                            $parentPage->id = 0;
                            $parentPage->group_container = 0;
                        }

                        $this->pageLevels[$i] = self::_loadSubPage($currentSegment, $languageId, $parentPage);

                        if (empty($this->pageLevels[$i])) {
                            if (self::_isSearchPage($currentSegment, $parentPage)) {
                                Search::setSearchBlockRequired();
                                $this->searchQuery = implode('/', array_slice(Request::segments(), $i));
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
                break;
            }

        }

        if ($this->searchQuery === false & $query = Request::get('q')) {
            $this->searchQuery = $query;
        }

        $this->pageLevels = array_filter($this->pageLevels);

    }

    /**
     * Load current page status.
     * Is live, Is preview, External Template
     */
    protected function _loadPageStatus()
    {
        $lowestLevelPage = count($this->pageLevels) > 0 ? $this->pageLevels[count($this->pageLevels) - 1] : null;

        if ($lowestLevelPage) {

            $this->isLive = $lowestLevelPage->is_live();

            if (!$this->is404) {
                $previewKey = Request::input('preview');
                if (!empty($previewKey)) {
                    $pageVersion = PageVersion::where('page_id', '=', $lowestLevelPage->id)->where('preview_key', '=', $previewKey)->first();
                    if (!empty($pageVersion)) {
                        $lowestLevelPage->page_lang[0]->live_version = $pageVersion->version_id;
                        $lowestLevelPage->template = $pageVersion->template;
                        $this->isPreview = true;
                    }
                }
            }

        }

        if($externalTemplate = Request::get('external')) {
            $this->externalTemplate = $externalTemplate;
        }

    }

    /**
     * @param int $languageId
     * @return Page|null
     */
    protected function _loadHomePage($languageId)
    {
        $paths = ['', '/'];
        return self::_pageQuery($paths, $languageId, 0);
    }

    /**
     * @param string $path
     * @param int $languageId
     * @param Page $parentPage
     * @return Page|null
     */
    protected function _loadSubPage($path, $languageId, Page $parentPage)
    {
        $paths = [$path];
        $page = self::_pageQuery($paths, $languageId, $parentPage->id);

        if (!$page && $parentPage->group_container > 0) {
            $page = self::_pageQuery($paths, $languageId, false, $parentPage->group_container);
        }

        return $page;
    }

    /**
     * @param string $path
     * @param int $languageId
     * @param bool|int $byParentId
     * @param bool|int $byGroupContainerId
     * @return Page|null
     */
    protected function _pageQuery($path, $languageId, $byParentId = false, $byGroupContainerId = false)
    {
        /** @var Builder $pageQuery */
        $pageQuery = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')
            ->with(['page_lang' => function ($query) use($path, $languageId) {
                self::_pageLangQuery($query, $path, $languageId);
            }]);

        if ($byParentId !== false) {
            $pageQuery->where('parent', '=', $byParentId);
        }

        if ($byGroupContainerId !== false) {
            $pageQuery->where('in_group', '=', $byGroupContainerId);
        }

        /** @var Page $page */
        $page = self::_pageLangQuery($pageQuery, $path, $languageId)->first(['pages.*']);

        if (!empty($page) && !$page->page_lang->isEmpty()) {
            return $page;
        } else {
            return null;
        }
    }

    /**
     * @param Builder $query
     * @param array $paths
     * @param int $languageId
     * @return Builder
     */
    protected function _pageLangQuery($query, $paths, $languageId)
    {
        return $query->where('page_lang.language_id', '=', $languageId)->whereIn('page_lang.url', $paths);
    }

    /**
     * @param string $path
     * @param Page $parentPage
     * @return bool
     */
    protected function _isSearchPage($path, Page $parentPage)
    {
        if ($path == 'search' || (!$parentPage->page_lang->isEmpty() && $parentPage->page_lang[0]->url == 'search')) {
            return true;
        } else {
            return false;
        }
    }

}