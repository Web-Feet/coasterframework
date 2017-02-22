<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\PageBuilderInstance;

/**
 * Class PageBuilder
 * @package CoasterCms\Libraries\Builder
 * @method static string themePath()
 * @method static string templatePath()
 * @method static bool canCache(null|int $setValue = null)
 * @method static int pageId(bool $noOverride = false)
 * @method static int parentPageId(bool $noOverride = false)
 * @method static int pageTemplateId(bool $noOverride = false)
 * @method static int pageLiveVersionId(bool $noOverride = false)
 * @method static string pageUrlSegment(int $pageId = 0, bool $noOverride = false)
 * @method static string pageUrl(int $pageId = 0, bool $noOverride = false)
 * @method static string pageName(int $pageId = 0, bool $noOverride = false)
 * @method static string pageFullName(int $pageId = 0, bool $noOverride = false, string $sep = ' &raquo; ')
 * @method static string pageVersion(int $pageId = 0, bool $noOverride = false)
 * @method static string img(string $fileName, array $options = [])
 * @method static string css(string $fileName)
 * @method static string js(string $fileName)
 * @method static void setCustomBlockData(string $blockName, mixed $content, int $key = 0, bool $overwrite = true)
 * @method static void setCustomBlockDataKey(string $key)
 * @method static string getCustomBlockDataKey()
 * @method static string external(string $section)
 * @method static string section(string $section)
 * @method static string breadcrumb(array $options = [])
 * @method static string menu(string $menuName, array $options = [])
 * @method static string sitemap(array $options = [])
 * @method static string category(array $options = [])
 * @method static string categoryLink(string $direction = 'next')
 * @method static string filter(string $blockName, string $search, array $options = [])
 * @method static string categoryFilter(string $blockName, string $search, array $options = [])
 * @method static string categoryFilters(array $blockNames, string $search, array $options = [])
 * @method static string search(array $options = [])
 * @method static string block(string $blockName, $options = [])
 * @method static mixed blockData(string $blockName, $options = [])
 * @method static \PDOStatement blogPosts(int $getPosts = 3, string $where = 'post_type = "post" AND post_status = "publish"')
 */

class PageBuilder
{
    /**
     * @var PageBuilderInstance
     */
    protected static $_instance;

    /**
     * @var string
     */
    protected static $_instanceClass;

    /**
     * @var array
     */
    protected static $_instanceArgs;
    
    /**
     * @var string
     */
    protected static $_loaderClass;

    /**
     * @var array
     */
    protected static $_loaderArgs;

    /**
     * @return PageBuilderInstance
     */
    protected static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static::$_instanceClass(new static::$_loaderClass(...static::$_loaderArgs), ...static::$_instanceArgs);
        }
        return static::$_instance;
    }

    /**
     * @param string $instanceClass
     * @param string $instanceArgs
     * @param string $loaderClass
     * @param array $loaderArgs
     */
    public static function setClass($instanceClass, $instanceArgs, $loaderClass, $loaderArgs)
    {
        static::$_instanceClass = $instanceClass;
        static::$_instanceArgs = $instanceArgs;
        static::$_loaderClass = $loaderClass;
        static::$_loaderArgs = $loaderArgs;
        static::$_instance = null;
    }

    /**
     * @param string $varName
     * @return mixed
     */
    public static function getData($varName = '')
    {
        $varName = camel_case($varName);
        if ($varName) {
            return property_exists(static::getInstance(), $varName) ? static::getInstance()->$varName : null;
        } else {
            return get_object_vars(static::getInstance());
        }
    }

    /**
     * @param string $varName
     * @param mixed $value
     */
    public static function setData($varName, $value)
    {
        if ($varName && property_exists(static::getInstance(), $varName)) {
            static::getInstance()->$varName = $value;
        }
    }

    /**
     * @param $method_name
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method_name, $args)
    {
        try {
            return call_user_func_array([static::getInstance(), $method_name], $args);
        } catch (PageBuilderException $e) {
            return 'PageBuilder error: ' . $e->getMessage();
        }
    }

}