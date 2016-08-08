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
    public $groupContainers;

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
        $this->groupContainers = [];
    }

    /**
     * @param array $pageIds
     * @param Path|null $parentPathData
     */
    protected static function _loadSubPaths($pageIds, $parentPathData = null)
    {
        foreach ($pageIds as $pageId) {
            $pageData = Page::preload($pageId);

            $pagePathData = self::_getById($pageId);
            $pagePathData->fullName = ($parentPathData ? $parentPathData->fullName . $pagePathData->separator : '')  . $pagePathData->name;
            if ($pageData->link > 0) {
                $pagePathData->fullUrl = $pagePathData->url;
            } else {
                $pagePathData->fullUrl = ($parentPathData ? $parentPathData->fullUrl : '') . '/' . $pagePathData->url;
                $pagePathData->fullUrl = $pagePathData->fullUrl == '//' ? '/' : $pagePathData->fullUrl;
            }

            if ($childPageIds = Page::child_page_ids($pageId)) {
                self::_loadSubPaths($childPageIds, $pagePathData);
            } else {
                if ($pageData->group_container > 0) {
                    $group = PageGroup::find($pageData->group_container);
                    if (!empty($group)) {
                        foreach ($group->itemPageFiltered($pageId) as $groupPage) {
                            $groupPagePathData = self::_getById($groupPage->id);
                            $groupPagePathData->groupContainers[$pageId] = [
                                'name' => $pagePathData->fullName,
                                'url' => $pagePathData->fullUrl,
                                'priority' => $groupPage->group_canonical == $pageId ? pow(2, 31)-1 : ($pageData->group_container_url_priority ?: $group->url_priority)
                            ];
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
            $loadedIds = [];
            foreach (self::$_preLoaded as $pageId => $pagePathData) {
                if ($pagePathData->groupContainers) {
                    uasort($pagePathData->groupContainers, function ($a, $b) {
                        if ($a['priority'] == $b['priority']) {
                            return 0;
                        }
                        return ($a['priority'] > $b['priority']) ? -1 : 1;
                    });
                    reset($pagePathData->groupContainers);
                    $groupPath = current($pagePathData->groupContainers);
                    if ($groupPath['priority'] > 100) {
                        $pagePathData->fullName = $groupPath['name'] . $pagePathData->separator . $pagePathData->name;
                        $pagePathData->fullUrl = $groupPath['url'] . '/' . $pagePathData->url;
                    }
                }
                $loadedIds[] = $pageId;
            }
            $missingPages = Page::whereNotIn('id', $loadedIds)->get();
            foreach ($missingPages as $missingPage) {
                self::_getById($missingPage->id);
            }
        }
    }

    /**
     * @param $pageId
     * @return Path
     */
    protected static function _getById($pageId)
    {
        if (empty(self::$_preLoaded[$pageId])) {
            $pageLang = PageLang::preload($pageId);
            self::$_preLoaded[$pageId] = new self;
            self::$_preLoaded[$pageId]->pageId = $pageId;
            self::$_preLoaded[$pageId]->name = $pageLang->name;
            self::$_preLoaded[$pageId]->url = $pageLang->url;
        }
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
        if ($groupContainerPageId) {
            $pagePathData = clone $pagePathData;
            $pagePathData->fullName = $pagePathData->groupContainers[$groupContainerPageId]['name'] . $pagePathData->separator . $pagePathData->name;
            $pagePathData->fullUrl = $pagePathData->groupContainers[$groupContainerPageId]['url'] . '/' . $pagePathData->url;
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
     * @return int
     */
    public static function parsePageId($unParsedPageId, $returnFirstEl = true)
    {
        $parts = explode(',', $unParsedPageId);
        return $returnFirstEl ? (int) $parts[0] : $parts;
    }

}