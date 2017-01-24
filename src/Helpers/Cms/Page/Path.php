<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageLang;

class Path
{
    /**
     * @var bool
     */
    public $exists;

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
    public $groupContainers;

    /**
     * @var Path[]
     */
    protected static $_preLoaded = [];

    /**
     * Path constructor.
     * @param bool $exists
     */
    public function __construct($exists)
    {
        $this->exists = $exists;
        $this->separator = '{sep}';
        $this->name = 'Not set';
        $this->url = 'not_set';
        $this->groupContainers = [];
    }

    /**
     * @param Page[] $pages
     * @param Path|null $parentPathData
     */
    protected static function _loadSubPaths($pages, $parentPathData = null)
    {
        foreach ($pages as $pageId => $pageData) {

            $pagePathData = self::_getById($pageId);
            $pagePathData->fullName = ($parentPathData ? $parentPathData->fullName . $pagePathData->separator : '')  . $pagePathData->name;
            if ($pageData->link > 0) {
                $pagePathData->fullUrl = $pagePathData->url;
            } else {
                $pagePathData->fullUrl = ($parentPathData ? $parentPathData->fullUrl : '') . '/' . $pagePathData->url;
            }

            if ($childPages = Page::getChildPages($pageId)) {
                self::_loadSubPaths($childPages, $pagePathData);
            }
            if ($pageData->group_container > 0) {
                $group = PageGroup::preload($pageData->group_container);
                if ($group->exists) {
                    foreach ($group->itemPageFiltered($pageId) as $groupPage) {
                        $groupPagePathData = self::_getById($groupPage->id);
                        $groupPagePathData->groupContainers[$pageId] = [
                            'name' => $pagePathData->fullName,
                            'url' => $pagePathData->fullUrl,
                            'priority' => $pageData->group_container_url_priority ?: $group->url_priority,
                            'canonical' => $groupPage->canonical_parent == $pageData->id
                        ];
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
            $topLevelPages = Page::getChildPages(0);
            self::_loadSubPaths($topLevelPages);
            $loadedIds = [];
            foreach (self::$_preLoaded as $pageId => $pagePathData) {
                if ($pagePathData->groupContainers) {
                    uasort($pagePathData->groupContainers, function ($a, $b) {
                        if ($a['canonical']) {
                            return -1;
                        }
                        if ($b['canonical']) {
                            return 1;
                        }
                        if ($a['priority'] == $b['priority']) {
                            if ($a['url'] == $b['url']) {
                                return 0;
                            }
                            return ($a['url'] < $b['url']) ? -1 : 1;
                        }
                        return ($a['priority'] > $b['priority']) ? -1 : 1;
                    });
                    reset($pagePathData->groupContainers);
                    $groupPath = current($pagePathData->groupContainers);
                    if ($groupPath['canonical'] || $groupPath['priority'] > 100 || is_null($pagePathData->fullUrl)) {
                        $pagePathData->fullName = $groupPath['name'] . $pagePathData->separator . $pagePathData->name;
                        $pagePathData->fullUrl = rtrim($groupPath['url'], '/') . '/' . $pagePathData->url;
                    }
                }
                $loadedIds[] = $pageId;
            }
            foreach (Page::preloadArray() as $pageId => $page) {
                if (!in_array($pageId, $loadedIds)) {
                    self::_getById($pageId);
                }
            }
        }
    }

    /**
     * @param string|int $pageId
     * @return Path
     */
    protected static function _getById($pageId)
    {
        if (empty(self::$_preLoaded[$pageId])) {
            $pageLang = PageLang::preload($pageId);
            self::$_preLoaded[$pageId] = new self($pageLang->exists);
            self::$_preLoaded[$pageId]->pageId = $pageId;
            self::$_preLoaded[$pageId]->name = $pageLang->name;
            self::$_preLoaded[$pageId]->url = rtrim($pageLang->url, '/');
        }
        return self::$_preLoaded[$pageId];
    }

    /**
     * @param string|int $pageId
     * @return Path
     */
    public static function getById($pageId)
    {
        self::_preLoad();
        $pageData = self::unParsePageId($pageId, false);
        $pageId = $pageData[0];
        $pagePathData = self::_getById($pageId);

        $groupContainerPageId = !empty($pageData[1]) ? $pageData[1] : 0;
        if ($groupContainerPageId && !empty($pagePathData->groupContainers[$groupContainerPageId])) {
            $pagePathData = clone $pagePathData;
            $pagePathData->fullName = $pagePathData->groupContainers[$groupContainerPageId]['name'] . $pagePathData->separator . $pagePathData->name;
            $pagePathData->fullUrl = $pagePathData->groupContainers[$groupContainerPageId]['url'] . '/' . $pagePathData->url;
        }

        return $pagePathData;
    }

    /**
     * @param string $pageId
     * @param \stdClass $data
     */
    public static function addCustomPagePath($pageId, $data)
    {
        $customPagePath = self::_getById($pageId);
        foreach ($data as $property => $value) {
            $customPagePath->$property = $value;
        }
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
        $pagePathData->fullName = $pagePathData->fullName ? str_replace($pagePathData->separator, $separator, $pagePathData->fullName) : $pagePathData->fullName;
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
            $paths[$pageId] = self::getFullPath($pageId, $separator);
        }
        return $paths;
    }

    /**
     * Get paths with group variations
     * @param array $pageIds
     * @param string $separator
     * @return Path[]
     */
    public static function getFullPathsVariations($pageIds, $separator = ' &raquo; ')
    {
        $paths = [];
        foreach ($pageIds as $pageId) {
            $paths[$pageId] = self::getFullPath($pageId, $separator);
            if ($paths[$pageId]->groupContainers) {
                foreach ($paths[$pageId]->groupContainers as $groupContainerPageId => $groupContainer) {
                    if ($paths[$pageId]->fullUrl != $groupContainer['url'] . '/' . $paths[$pageId]->url) {
                        $parsedId = self::parsePageId($pageId, $groupContainerPageId);
                        $paths[$parsedId] = self::getFullPath($parsedId, $separator);
                    }
                }
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
        self::_preLoad();
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
     * @return int|string|array
     */
    public static function unParsePageId($unParsedPageId, $returnFirstEl = true)
    {
        $parts = explode(',', $unParsedPageId);
        return $returnFirstEl ? $parts[0] : $parts;
    }

    /**
     * @param int $pageId
     * @param int $groupContainerPageId
     * @return string
     */
    public static function parsePageId($pageId, $groupContainerPageId = 0)
    {
        return $pageId . ($groupContainerPageId ? ',' . $groupContainerPageId : '');
    }

}