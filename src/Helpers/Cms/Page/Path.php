<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageLang;

class Path
{
    /**
     * @var int
     */
    public $pageId;

    /**
     * @var string
     */
    public $separator;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $fullName;

    /**
     * @var string
     */
    public $fullUrl;

    /**
     * @var array
     */
    public $groupContainerNames;

    /**
     * @var array
     */
    public $groupContainerUrls;

    /**
     * @var int
     */
    public $groupContainerDefault;

    /**
     * @var Path[]
     */
    protected static $_preLoaded = [];

    /**
     * Path constructor.
     */
    public function __construct()
    {
        $this->separator = '{sep}';
        $this->name = 'Not set';
        $this->url = 'not_set';
        $this->groupContainerNames = [];
        $this->groupContainerUrls = [];
        $this->groupContainerDefault = 0;
    }

    /**
     * @param array $pageIds
     * @param Path|null $parentPathData
     */
    protected static function _loadSubPaths($pageIds, $parentPathData = null)
    {
        $groups = PageGroup::all();
        $groupsArray = [];
        foreach ($groups as $group) {
            $groupsArray[$group->id] = $group;
        }
        foreach ($pageIds as $pageId) {
            $pageLang = PageLang::preload($pageId);
            $pageData = Page::preload($pageId);

            $pagePathData = self::_getById($pageId);
            $pagePathData->pageId = $pageId;
            $pagePathData->name = $pageLang->name;
            $pagePathData->url = $pageLang->url;

            $pagePathData->fullName = ($parentPathData ? $parentPathData->fullName . $pagePathData->separator : '')  . $pagePathData->name;
            if ($pageData->link > 0) {
                $pagePathData->fullUrl = $pagePathData->url;
            } else {
                $pagePathData->fullUrl = ($parentPathData ? $parentPathData->fullUrl : '') . '/' . $pagePathData->url;
                if ($pagePathData->fullUrl == '//') {
                    $pagePathData->fullUrl = '/';
                }
            }

            if ($childPageIds = Page::child_page_ids($pageId)) {
                self::_loadSubPaths($childPageIds, $pagePathData);
            } else {
                if ($pageData->group_container > 0) {
                    $group = PageGroup::find($pageData->group_container);
                    if (!empty($group)) {
                        foreach ($group->itemPageIdsFiltered($pageId) as $groupPageId) {
                            $groupPageLang = PageLang::preload($groupPageId);
                            $groupPagePathData = self::_getById($groupPageId);
                            $groupPagePathData->name = $groupPageLang->name;
                            $groupPagePathData->url = $groupPageLang->url;
                            $groupPagePathData->groupContainerNames[$pageId] = $pagePathData->fullName;
                            $groupPagePathData->groupContainerUrls[$pageId] = $pagePathData->fullUrl;
                            $groupPagePathData->groupContainerDefault = $group->default_parent;
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected static function _preLoad()
    {
        if (!self::$_preLoaded) {
            $topLevelPages = Page::child_page_ids(0);
            self::_loadSubPaths($topLevelPages);
        }
    }

    /**
     * @param $pageId
     * @return Path
     */
    protected static function _getById($pageId)
    {
        self::$_preLoaded[$pageId] = !empty(self::$_preLoaded[$pageId]) ? self::$_preLoaded[$pageId] : new self;
        return self::$_preLoaded[$pageId];
    }

    /**
     * @param $pageId
     * @return Path
     */
    public static function getById($pageId)
    {
        self::_preLoad();
        $pageData = self::parsePageId($pageId, false);
        $pageId = $pageData[0];
        $pagePathData = !empty(self::$_preLoaded[$pageId]) ? self::$_preLoaded[$pageId] : new self;

        $groupContainerPageId = !empty($pageData[1]) ? $pageData[1] : 0;
        if (!$pagePathData->fullUrl) {
            $pagePathData = clone $pagePathData;
            $groupContainerPageId = $groupContainerPageId ?: $pagePathData->groupContainerDefault;
            if (!empty($pagePathData->groupContainerUrls[$groupContainerPageId])) {
                $pagePathData->fullName = $pagePathData->groupContainerNames[$groupContainerPageId] . $pagePathData->separator . $pagePathData->name;
                $pagePathData->fullUrl = $pagePathData->groupContainerUrls[$groupContainerPageId] . '/' . $pagePathData->url;
            }
        }

        return $pagePathData;
    }

    /**
     * @param int|string $pageId
     * @return string
     */
    public static function getFullUrl($pageId)
    {
        return Path::getById($pageId)->fullUrl;
    }

    /**
     * @param int|string $pageId
     * @param string $separator
     * @return string
     */
    public static function getFullName($pageId, $separator = ' &raquo; ')
    {
        $pagePathData = Path::getById($pageId);
        return str_replace($pagePathData->separator, $separator, $pagePathData->fullName);
    }

    /**
     * @param int|string $pageId
     * @param string $separator
     * @return Path
     */
    public static function getFullPath($pageId, $separator = ' &raquo; ')
    {
        $pagePathData = Path::getById($pageId);
        $pagePathData->fullName = str_replace($pagePathData->separator, $separator, $pagePathData->fullName);
        return $pagePathData;
    }
    
    /**
     * @param array $pageIds
     * @param string $separator
     * @return Path[]
     */
    public static function getFullPaths($pageIds, $separator = ' &raquo; ')
    {
        $paths = [];
        foreach ($pageIds as $pageId) {
            $addPagePath = true;
            $pagePathData = self::getFullPath($pageId, $separator);
            if ($pagePathData->groupContainerUrls) {
                foreach ($pagePathData->groupContainerUrls as $groupContainerPageId => $fullUrl) {
                    $groupPageId = $pageId . ',' . $groupContainerPageId;
                    $paths[$groupPageId] = self::getFullPath($groupPageId, $separator);
                    if ($paths[$groupPageId]->fullUrl == $pagePathData->fullUrl) {
                        $addPagePath = false;
                    }
                }
            }
            // if default route is not a group route and not blank
            if ($addPagePath && $pagePathData->fullUrl) {
                $paths[$pageId] = $pagePathData;
            }
        }
        return $paths;
    }

    /**
     * @param string $separator
     * @return array
     */
    public static function all($separator = ' &raquo; ')
    {
        $paths = [];
        foreach (self::$_preLoaded as $pageId => $path) {
            $paths[$pageId] = self::getFullPath($pageId, $separator);
        }
        return $paths;
    }

    /**
     * remove group data from saved block content or block options
     * @param int|string $unParsedPageId
     * @param bool $returnFirstEl
     * @return int
     */
    public static function parsePageId($unParsedPageId, $returnFirstEl = true)
    {
        $parts = explode(',', $unParsedPageId);
        return $returnFirstEl ? (int) $parts[0] : $parts;
    }

}