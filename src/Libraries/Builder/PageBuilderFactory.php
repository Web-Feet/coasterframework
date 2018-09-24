<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Contracts\PageBuilder;
use Illuminate\Support\Collection;

class PageBuilderFactory implements PageBuilder
{

    /**
     * @var PageBuilderLogger[]
     */
    protected $_instances;

    /**
     * @var string
     */
    protected $_activeInstance;

    /**
     * @var int
     */
    protected $_unNamedIndex;

    /**
     * @var string
     */
    protected $_defaultClass;

    /**
     * @var array
     */
    protected $_defaultArgs;

    /**
     * PageBuilderFactory constructor.
     * @param string $defaultClass
     * @param array $defaultArgs
     */
    public function __construct($defaultClass = '', $defaultArgs = [])
    {
        $this->_defaultClass = $defaultClass;
        $this->_defaultArgs = $defaultArgs;
        $this->_instances = [];
        $this->_unNamedIndex = 0;
        $this->_activeInstance = 'default';
    }

    /**
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @param bool $setActive
     * @return PageBuilderLogger
     */
    public function make($pageBuilderClass, $pageBuilderArgs, $setActive = true)
    {
        return $this->setInstance('', $pageBuilderClass, $pageBuilderArgs, $setActive);
    }

    /**
     * @param string $activeInstance
     */
    public function switchActiveInstance($activeInstance)
    {
        $this->_activeInstance = $activeInstance;
    }

    /**
     * @param string $name
     * @return PageBuilderLogger
     */
    public function getInstance($name = null)
    {
        $name = is_null($name) ? $this->_activeInstance : $name;
        return $this->setInstance($name);
    }

    /**
     * @param string $name
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @param bool $setActive
     * @return PageBuilderLogger
     */
    public function setInstance($name = '', $pageBuilderClass = '', $pageBuilderArgs = [], $setActive = true)
    {
        if ($name === '') {
            $name = $this->_unNamedIndex++;
            while (array_key_exists($name, $this->_instances)) {
                $name = $this->_unNamedIndex++;
            }
        }
        if (!array_key_exists($name, $this->_instances)) {
            $pageBuilderArgs = ($pageBuilderClass || $pageBuilderArgs) ? $pageBuilderArgs : $this->_defaultArgs;
            $pageBuilderClass = $pageBuilderClass ?: $this->_defaultClass;
            $this->_instances[$name] = new PageBuilderLogger($pageBuilderClass, $pageBuilderArgs);
        }
        if ($setActive) {
            $this->switchActiveInstance($name);
        }
        return $this->_instances[$name];
    }

    /**
     * @return Collection
     */
    public function logs()
    {
        return $this->_call('logs');
    }

    /**
     * @param bool $state
     */
    public function setLogState($state)
    {
        $this->_call('setLogState', [$state]);
    }

    /**
     * @return string
     */
    public function themePath()
    {
        return $this->_call('themePath');
    }

    /**
     * @param bool $withThemePath
     * @return string
     */
    public function templatePath($withThemePath = true)
    {
        return $this->_call('templatePath', [$withThemePath]);
    }

    /**
     * @param string $customTemplate
     * @param array $viewData
     * @return string
     */
    public function templateRender($customTemplate = null, $viewData = [])
    {
        return $this->_call('templateRender', [$customTemplate, $viewData]);
    }

    /**
     * @param int $setValue
     * @return bool
     */
    public function canCache($setValue = null)
    {
        return $this->_call('canCache', [$setValue]);
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageId($noOverride = false)
    {
        return $this->_call('pageId', [$noOverride]);
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function parentPageId($noOverride = false)
    {
        return $this->_call('parentPageId', [$noOverride]);
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageTemplateId($noOverride = false)
    {
        return $this->_call('pageTemplateId', [$noOverride]);
    }

    /**
     * @param bool $noOverride
     * @return int
     */
    public function pageLiveVersionId($noOverride = false)
    {
        return $this->_call('pageLiveVersionId', [$noOverride]);
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrlSegment($pageId = 0, $noOverride = false)
    {
        return $this->_call('pageUrlSegment', [$pageId, $noOverride]);
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageUrl($pageId = 0, $noOverride = false)
    {
        return $this->_call('pageUrl', [$pageId, $noOverride]);
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return string
     */
    public function pageName($pageId = 0, $noOverride = false)
    {
        return $this->_call('pageName', [$pageId, $noOverride]);
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @param string $sep
     * @return string
     */
    public function pageFullName($pageId = 0, $noOverride = false, $sep = ' &raquo; ')
    {
        return $this->_call('pageFullName', [$pageId, $noOverride, $sep]);
    }

    /**
     * @param int $pageId
     * @param bool $noOverride
     * @return int
     */
    public function pageVersion($pageId = 0, $noOverride = false)
    {
        return $this->_call('pageVersion', [$pageId, $noOverride]);
    }

    /**
     * @param bool $noOverride
     * @return string
     */
    public function pageJson($noOverride = false)
    {
        return $this->_call('pageJson', [$noOverride]);
    }

    /**
     * @param string $fileName
     * @param array $options
     * @return string
     */
    public function img($fileName, $options = [])
    {
        return $this->_call('img', [$fileName, $options]);
    }

    /**
     * @param $fileName
     * @return string
     */
    public function css($fileName)
    {
        return $this->_call('css', [$fileName]);
    }

    /**
     * @param $fileName
     * @return string
     */
    public function js($fileName)
    {
        return $this->_call('js', [$fileName]);
    }

    /**
     * @param string $blockName
     * @param mixed $content
     * @param int $key
     * @param bool $overwrite
     */
    public function setCustomBlockData($blockName, $content, $key = 0, $overwrite = true)
    {
        $this->_call('setCustomBlockData', [$blockName, $content, $key, $overwrite]);
    }

    /**
     * @param string $section
     * @return string
     */
    public function external($section)
    {
        return $this->_call('external', [$section]);
    }

    /**
     * @param string $section
     * @param array $viewData
     * @return string
     */
    public function section($section, $viewData = [])
    {
        return $this->_call('section', [$section, $viewData]);
    }

    /**
     * @param array $options
     * @return string
     */
    public function breadcrumb($options = [])
    {
        return $this->_call('breadcrumb', [$options]);
    }

    /**
     * @param string $menuName
     * @param array $options
     * @return string
     */
    public function menu($menuName, $options = [])
    {
        return $this->_call('menu', [$menuName, $options]);
    }

    /**
     * @param array $options
     * @return string
     */
    public function sitemap($options = [])
    {
        return $this->_call('sitemap', [$options]);
    }

    /**
     * @param int $categoryPageId
     * @param array|null $pages
     * @param array $options
     * @return string
     */
    public function pages($categoryPageId = null, $pages = null, $options = [])
    {
        return $this->_call('pages', [$categoryPageId, $pages, $options]);
    }

    /**
     * @param array $options
     * @return string
     */
    public function category($options = [])
    {
        return $this->_call('category', [$options]);
    }

    /**
     * @param string $direction
     * @return string
     */
    public function categoryLink($direction = 'next')
    {
        return $this->_call('categoryLink', [$direction]);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function filter($blockName, $search, $options = [])
    {
        return $this->_call('filter', [$blockName, $search, $options]);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function categoryFilter($blockName, $search, $options = [])
    {
        return $this->_call('categoryFilter', [$blockName, $search, $options]);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function categoryFilters($blockName, $search, $options = [])
    {
        return $this->_call('categoryFilters', [$blockName, $search, $options]);
    }

    /**
     * @param array $options
     * @return string
     */
    public function search($options = [])
    {
        return $this->_call('search', [$options]);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function block($blockName, $options = [])
    {
        return $this->_call('block', [$blockName, $options]);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function blockData($blockName, $options = [])
    {
        return $this->_call('blockData', [$blockName, $options]);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @return string
     */
    public function blockJson($blockName, $options = [])
    {
        return $this->_call('blockJson', [$blockName, $options]);
    }

    /**
     * @param int $getPosts
     * @param string $where
     * @return \PDOStatement
     */
    public function blogPosts($getPosts = 3, $where = 'post_type = "post" AND post_status = "publish"')
    {
        return $this->_call('blogPosts', [$getPosts, $where]);
    }

    /**
     * @param string $varName
     * @return mixed
     */
    public function getData($varName = '')
    {
        return $this->_call('getData', [$varName]);
    }

    /**
     * @param string $varName
     * @param mixed $value
     * @return mixed
     */
    public function setData($varName, $value)
    {
        return $this->_call('setData', [$varName, $value]);
    }

    /**
     * call active logger instance
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function _call($methodName, $args = [])
    {
        return call_user_func_array([$this->getInstance(), $methodName], $args);
    }

    /**
     * call any custom methods on active logger instance
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        return call_user_func_array([$this->getInstance(), $methodName], $args);
    }



}
