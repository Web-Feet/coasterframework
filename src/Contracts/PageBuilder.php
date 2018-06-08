<?php

namespace CoasterCms\Contracts;

use Illuminate\Support\Collection;

interface PageBuilder
{
    /**
     * @return Collection
     */
    public function logs();

    /**
     * @param bool $state
     */
    public function setLogState($state);

    /**
     * @return string
     */
    public function themePath();

    /**
     * @return string
     */
    public function templatePath();

    /**
     * @param string $customTemplate
     * @param array $viewData
     * @return string
     */
    public function templateRender($customTemplate = null, $viewData = []);

    /**
     * @param int $setValue
     * @return bool
     */
    public function canCache($setValue = null);

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageId($noOverride = false);

    /**
     * @param bool $noOverride
     * @return int
     */
    public function parentPageId($noOverride = false);

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageTemplateId($noOverride = false);

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageLiveVersionId($noOverride = false);

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrlSegment($pageId = 0, $noOverride = false);

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrl($pageId = 0, $noOverride = false);

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageName($pageId = 0, $noOverride = false);

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @param string $sep
     * @return string
     */
    public function pageFullName($pageId = 0, $noOverride = false, $sep = ' &raquo; ');

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return int
     */
    public function pageVersion($pageId = 0, $noOverride = false);

    /**
     * @param bool $noOverride
     * @return string
     */
    public function pageJson($noOverride = false);

    /**
     * @param string $fileName
     * @param array $options
     * @return string
     */
    public function img($fileName, $options = []);

    /**
     * @param $fileName
     * @return string
     */
    public function css($fileName);

    /**
     * @param $fileName
     * @return string
     */
    public function js($fileName);

    /**
     * @param string $blockName
     * @param mixed $content
     * @param int $key
     * @param bool $overwrite
     */
    public function setCustomBlockData($blockName, $content, $key = 0, $overwrite = true);

    /**
     * @param string $section
     * @return string
     */
    public function external($section);

    /**
     * @param string $section
     * @param array $viewData
     * @return string
     */
    public function section($section, $viewData = []);

    /**
     * @param array $options
     * @return string
     */
    public function breadcrumb($options = []);

    /**
     * @param string $menuName
     * @param array $options
     * @return string
     */
    public function menu($menuName, $options = []);

    /**
     * @param array $options
     * @return string
     */
    public function sitemap($options = []);

    /**
     * @param int $categoryPageId
     * @param array|null $pages
     * @param array $options
     * @return string
     */
    public function pages($categoryPageId = null, $pages = null, $options = []);

    /**
     * @param array $options
     * @return string
     */
    public function category($options = []);

    /**
     * @param string $direction
     * @return string
     */
    public function categoryLink($direction = 'next');

    /**
     * @param string|array $blockName
     * @param string|array $search
     * @param array $options
     * @return string
     */
    public function filter($blockName, $search, $options = []);

    /**
     * @param string|array $blockName
     * @param string|array $search
     * @param array $options
     * @return string
     */
    public function categoryFilter($blockName, $search, $options = []);

    /**
     * @param string|array $blockName
     * @param string|array $search
     * @param array $options
     * @return string
     */
    public function categoryFilters($blockName, $search, $options = []);

    /**
     * @param array $options
     * @return string
     */
    public function search($options = []);

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function block($blockName, $options = []);

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function blockData($blockName, $options = []);

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function blockJson($blockName, $options = []);

    /**
     * @param int $getPosts
     * @param string $where
     * @return \PDOStatement
     */
    public function blogPosts($getPosts = 3, $where = 'post_type = "post" AND post_status = "publish"');

    /**
     * @param string $varName
     * @return mixed
     */
    public function getData($varName = '');

    /**
     * @param string $varName
     * @param mixed $value
     * @return mixed
     */
    public function setData($varName, $value);

}
