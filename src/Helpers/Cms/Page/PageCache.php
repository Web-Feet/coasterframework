<?php namespace CoasterCms\Helpers\Cms\Page;

use Cache;

/**
 * Class PageCache
 * @package CoasterCms\Helpers\Cms\Page
 * @method static string clear(int $pageId = null)
 * @method static string forget(int $pageId, string $hash)
 * @method static string put(int $pageId, string $hash, string $content)
 * @method static string remember(int $pageId, string $hash, $callback)
 */
class PageCache
{

    /**
     * @var static
     */
    protected static $_instance;

    /**
     * @var array
     */
    protected $_keys;

    /**
     *
     */
    const PAGE_CACHE_KEY = 'fpc_page';

    /**
     * PageCache constructor.
     */
    public function __construct()
    {
        $this->_keys = Cache::get(static::PAGE_CACHE_KEY) ?: [];
        $this->_cacheFor = abs((int) config('coaster::frontend.cache'));
    }

    /**
     * @param int $pageId
     */
    public function _clear($pageId = null)
    {
        $pageKeys = is_null($pageId) ? $this->_keys : (array_key_exists($pageId, $this->_keys) ? [$this->_keys[$pageId]] : []);
        foreach ($pageKeys as $pageId => $requestHashes) {
            foreach ($requestHashes as $requestHash => $key) {
                Cache::forget($key);
            }
            unset($this->_keys[$pageId]);
        }
        $this->_saveKeys();
    }

    /**
     * @param int $pageId
     * @param string $hash
     */
    public function _forget($pageId, $hash)
    {
        $key = $this->_key($pageId, $hash);
        if (array_key_exists($pageId, $this->_keys) && array_key_exists($hash, $this->_keys[$pageId])) {
            unset($this->_keys[$pageId][$hash]);
        }
        $this->_saveKeys();
        Cache::forget($key);
    }

    /**
     * @param int $pageId
     * @param string $hash
     * @param string $content
     */
    public function _put($pageId, $hash, $content)
    {
        if ($this->_cacheFor > 0) {
            $key = $this->_key($pageId, $hash);
            Cache::put($key, $content, $this->_cacheFor);
            $this->_keys[$pageId][$hash] = $key;
            $this->_saveKeys();
        }
    }

    /**
     * @param int $pageId
     * @param string $hash
     * @param \Closure $callback
     * @return mixed
     */
    public function _remember($pageId, $hash, $callback)
    {
        if (array_key_exists($pageId, $this->_keys) && array_key_exists($hash, $this->_keys[$pageId]) && Cache::has($this->_keys[$pageId][$hash])) {
            $content = Cache::get($this->_keys[$pageId][$hash]);
        } else {
            $content = $callback();
            $this->_put($pageId, $hash, $content);
        }
        return $content;
    }

    /**
     *
     */
    protected function _saveKeys()
    {
        Cache::forever(static::PAGE_CACHE_KEY, $this->_keys);
    }

    /**
     * @param int $pageId
     * @param string $hash
     * @return string
     */
    protected function _key($pageId, $hash)
    {
        return static::PAGE_CACHE_KEY . '.' . $pageId . '.' . $hash;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static;
        }
        return static::$_instance->{'_'.$name}(...$arguments);
    }

}