<?php
namespace CoasterCms\Facades;

use CoasterCms\Libraries\Builder\PageBuilderFactory;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string logs()
 * @method static string setLogState($state)
 * @method static string themePath()
 * @method static string templatePath()
 * @method static string templateRender(string $customTemplate = null, array $viewData = [])
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
 * @method static string pageJson(bool $noOverride = false)
 * @method static string img(string $fileName, array $options = [])
 * @method static string css(string $fileName)
 * @method static string js(string $fileName)
 * @method static void setCustomBlockData(string $blockName, mixed $content, int $key = 0, bool $overwrite = true)
 * @method static string external(string $section)
 * @method static string section(string $section)
 * @method static string breadcrumb(array $options = [])
 * @method static string menu(string $menuName, array $options = [])
 * @method static string sitemap(array $options = [])
 * @method static string pages(int $categoryPageId = null, array $pages = null, array $options = [])
 * @method static string category(array $options = [])
 * @method static string categoryLink(string $direction = 'next')
 * @method static string filter(string $blockName, string $search, array $options = [])
 * @method static string categoryFilter(string $blockName, string $search, array $options = [])
 * @method static string categoryFilters(array $blockNames, string $search, array $options = [])
 * @method static string search(array $options = [])
 * @method static string block(string $blockName, $options = [])
 * @method static mixed blockData(string $blockName, $options = [])
 * @method static string blockJson(string $blockName, $options = [])
 * @method static string getData(string $varName = '')
 * @method static string setData(string $varName, mixed $value)
 * @method static \PDOStatement blogPosts(int $getPosts = 3, string $where = 'post_type = "post" AND post_status = "publish"')
 */
class PageBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pageBuilder';
    }

    /**
     * @return string
     */
    protected static function getMockableClass()
    {
        return PageBuilderFactory::class;
    }
}
