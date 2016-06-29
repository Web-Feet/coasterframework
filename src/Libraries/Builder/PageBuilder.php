<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\Instances\PageBuilderInstance;
use CoasterCms\Models\Page;

/**
 * Class PageBuilder
 * @package CoasterCms\Libraries\Builder
 * @method static void setTheme(int $themeId)
 * @method static void setTemplate(int|string $template)
 * @method static int pageId(bool $noOverride = false)
 * @method static int parentPageId(bool $noOverride = false)
 * @method static int pageTemplateId(bool $noOverride = false)
 * @method static int pageLiveVersionId(bool $noOverride = false)
 * @method static string pageUrlSegment(int $pageId = 0, bool $noOverride = false)
 * @method static string pageUrl(int $pageId = 0, bool $noOverride = false)
 * @method static string pageName(int $pageId = 0, bool $noOverride = false)
 * @method static string pageFullName(int $pageId = 0, bool $noOverride = false, string $sep = ' &raquo; ')
 * @method static string img(string $fileName, array $options = [])
 * @method static string css(string $fileName)
 * @method static string js(string $fileName)
 * @method static void setCustomBlockData(string $blockName, mixed $content, mixed $key = 0)
 * @method static void setCustomBlockDataKey(string $key)
 * @method static string external(string $section)
 * @method static string section(string $section)
 * @method static string breadcrumb(array $options = [])
 * @method static string menu(string $menuName, array $options = [])
 * @method static string sitemap(array $options = [])
 * @method static string category(array $options = [])
 * @method static string categoryLink(string $direction = 'next')
 * @method static string filter(string $blockName, string $search, array $options = [])
 * @method static string categoryFilter(string $blockName, string $search, array $options = [])
 * @method static string search(array $options = [])
 * @method static string block(string $blockName, $options = [])
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
     * @var string
     */
    protected static $_loaderClass;

    /**
     * @var array
     */
    protected static $_extraArgs;

    /**
     * @return PageBuilderInstance
     */
    protected static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static::$_instanceClass(new static::$_loaderClass, ...static::$_extraArgs);
        }
        return static::$_instance;
    }

    /**
     * @param string $instanceClass
     * @param string $loaderClass
     * @param array ...$extraArgs
     */
    public static function setClass($instanceClass, $loaderClass, ...$extraArgs)
    {
        static::$_instanceClass = $instanceClass;
        static::$_loaderClass = $loaderClass;
        static::$_extraArgs = $extraArgs;
        static::$_instance = null;
    }

    /**
     * @param string $varName
     * @return mixed
     */
    public static function getData($varName = '')
    {
        if ($varName) {
            return static::getInstance()->$varName;
        } else {
            return get_object_vars(static::getInstance());
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