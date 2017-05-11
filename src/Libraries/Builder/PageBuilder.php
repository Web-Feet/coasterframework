<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\PageBuilderInstance;
use Illuminate\Support\Traits\Macroable;

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
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var PageBuilderInstance[]
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
     * @return PageBuilderInstance
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
     * @return PageBuilderInstance
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
     * @return PageBuilderInstance
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
            $this->_instances[$name] = new $pageBuilderClass(...$pageBuilderArgs);
        }
        if ($setActive) {
            $this->switchActiveInstance($name);
        }
        return $this->_instances[$name];
    }

    /**
     * @param string $name
     */
    public function __get($name)
    {
        return $this->getInstance()->$name;
    }

    /**
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        try {
            if ($this->hasMacro($methodName)) {
                return $this->macroCall($methodName, $args);
            } else {
                return call_user_func_array([$this->getInstance(), $methodName], $args);
            }
        } catch (PageBuilderException $e) {
            return 'PageBuilder error: ' . $e->getMessage();
        }
    }

}