<?php
namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
     * @var DefaultInstance
     */
    protected $_pageBuilder;

    /**
     * @var Collection
     */
    protected $_logs;

    /**
     * @var bool
     */
    protected $_logEnabled;

    /**
     * @param string $key
     * @return Collection
     */
    public function logs($key = null)
    {
        if (!is_null($key)) {
            return $this->_logs->pluck($key);
        }
        return $this->_logs;
    }

    /**
     * @param bool $state
     */
    public function setLogState($state)
    {
        $this->_logEnabled = $state;
    }

    /**
     * PageBuilderLogger constructor.
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     */
    public function __construct($pageBuilderClass, $pageBuilderArgs)
    {
        $this->_logState = true;
        $this->_logs = collect([]);
        $this->_pageBuilder = new $pageBuilderClass($this, ...$pageBuilderArgs);
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        $logFn = 'debug';
        $logContext = ['method' => $methodName, 'args' => $args, 'macro' => false];
        try {
            if ($this->hasMacro($methodName)) {
                $logContext['macro'] = true;
                $return = $this->macroCall($methodName, $args);
            } else {
                $return = call_user_func_array([$this->_pageBuilder, $methodName], $args);
            }
        } catch (PageBuilderException $e) {
            $logFn = 'error';
            $return = 'PageBuilder error: ' . $e->getMessage();
        }
        if ($this->_logEnabled) {
            $this->_logs->push($logContext);
            Log::$logFn('PageBuilder method called: ' . $methodName, $logContext);
        }
        return $return;
    }

}
