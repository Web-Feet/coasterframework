<?php namespace CoasterCms\Libraries\Traits;

use Illuminate\Database\Eloquent\Collection;

trait DataPreLoad
{

    /**
     * @var &array
     */
    protected static $_preLoadedData;

    /**
     * @var array
     */
    protected static $_preLoadedCustomData = [];

    /**
     * @param string $key
     * @param bool $force
     * @return static
     */
    public static function preload($key, $force = false)
    {
        if (!static::_preloadIsset() || $force) {
            static::_preload();
        }
        return !empty(static::$_preLoadedData[$key]) ? static::$_preLoadedData[$key] : new static;
    }

    /**
     * @param string $key
     * @param bool $force
     * @return static
     */
    public static function preloadClone($key, $force = false)
    {
        return clone static::preload($key, $force);
    }

    /**
     * @param bool $force
     * @return array
     */
    public static function preloadArray($force = false)
    {
        if (!static::_preloadIsset() || $force) {
            static::_preload();
        }
        return static::$_preLoadedData;
    }

    /**
     * @param string $customDataSetKey
     * @param string $identifier
     * @return bool
     */
    protected static function _preloadIsset($customDataSetKey = 'default', $identifier = null)
    {
        if (isset(static::$_preLoadedCustomData[$customDataSetKey])) {
            return $identifier ? array_key_exists($identifier, static::$_preLoadedCustomData[$customDataSetKey]) : true;
        }
        return false;
    }

    /**
     * @param string $customDataSetKey
     */
    protected static function _preloadClear($customDataSetKey = 'default')
    {
        unset(static::$_preLoadedCustomData[$customDataSetKey]);
    }

    /**
     * @param string $customDataSetKey
     * @param string|array $key
     * @return mixed|null
     */
    protected static function _preloadGet($customDataSetKey = 'default', $key)
    {
        $data = static::_preloadGetArray($customDataSetKey);
        $key = is_array($key) ? $key : [$key];
        foreach ($key as $keyPart) {
            if (array_key_exists($keyPart, $data)) {
                $data = $data[$keyPart];
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * @param string $customDataSetKey
     * @return array
     */
    protected static function _preloadGetArray($customDataSetKey = 'default')
    {
        return static::_preloadIsset($customDataSetKey) ? static::$_preLoadedCustomData[$customDataSetKey] : [];
    }

    /**
     * @param string $customDataSetKey
     * @param string $identifier
     * @param mixed $value
     */
    protected static function _preloadAdd($customDataSetKey = 'default', $identifier, $value)
    {
        if (!static::_preloadIsset($customDataSetKey)) {
            static::$_preLoadedCustomData[$customDataSetKey] = [];
        }
        if (is_null($identifier)) {
            static::$_preLoadedCustomData[$customDataSetKey][] = $value;
        } else {
            static::$_preLoadedCustomData[$customDataSetKey][$identifier] = $value;
        }
    }

    /**
     * @param Collection $data
     * @param string $customDataSetKey
     * @param array $identifiers
     * @param string $storeSingleColumn
     * @param bool $allowMultiple
     */
    protected static function _preload($data = null, $customDataSetKey = 'default', $identifiers = [], $storeSingleColumn = null, $allowMultiple = false)
    {
        $data = $data ?: static::_preloadCollection();
        static::$_preLoadedCustomData[$customDataSetKey] = static::_preloadGetArray($customDataSetKey);
        $identifiers = $identifiers ?: static::_preloadByColumn();
        foreach ($identifiers as $k => $identifier) {
            if (!is_array($identifier)) {
                $identifiers[$k] = [$identifier];
            }
        }
        foreach ($data as $row) {
            foreach ($identifiers as $identifier) {
                $storeValue = $storeSingleColumn ? $row->$storeSingleColumn : $row;
                $identifierValues = array_map(function ($column) use($row) {return strpos($column, '@') === 0 ? substr($column, 1) : $row->$column;}, $identifier);
                static::_storeData(static::$_preLoadedCustomData[$customDataSetKey], $identifierValues, $storeValue, $allowMultiple);
            }
        }
        if ($customDataSetKey == 'default') {
            static::$_preLoadedData = &static::$_preLoadedCustomData[$customDataSetKey];
        }
    }

    /**
     * @param &array $store
     * @param array $identifiers
     * @param mixed $value
     * @param bool $multiStore
     */
    private static function _storeData(&$store, $identifiers, $value, $multiStore)
    {
        $isNew = false;
        foreach ($identifiers as $depth => $identifier) {
            if (!array_key_exists($identifier, $store)) {
                $store[$identifier] = [];
                $isNew = true;
            }
            $store = &$store[$identifier];
        }
        if ($multiStore) {
            $store[] = $value;
        } elseif ($isNew) {
            $store = $value;
        }
    }

    /**
     * @param null $data
     * @param string $customDataSetKey
     * @param array $identifiers
     * @param null $storeSingleColumn
     * @param bool $allowMultiple
     */
    protected static function _preloadOnce($data = null, $customDataSetKey = 'default', $identifiers = [], $storeSingleColumn = null, $allowMultiple = false)
    {
        if (!static::_preloadIsset($customDataSetKey)) {
            static::_preload($data, $customDataSetKey, $identifiers, $storeSingleColumn, $allowMultiple);
        }
    }

    /**
     * @return array
     */
    protected static function _preloadByColumn()
    {
        return ['id'];
    }

    /**
     * @return Collection
     */
    protected static function _preloadCollection()
    {
        return static::all();
    }

}