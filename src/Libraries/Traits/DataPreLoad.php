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
     * @return bool
     */
    protected static function _preloadIsset($customDataSetKey = 'default')
    {
        return isset(static::$_preLoadedCustomData[$customDataSetKey]);
    }

    /**
     * @param string $customDataSetKey
     * @param string $key
     * @return mixed|null
     */
    protected static function _preloadGet($customDataSetKey = 'default', $key = '')
    {
        return static::_preloadIsset($customDataSetKey) && array_key_exists($key, static::$_preLoadedCustomData[$customDataSetKey]) ? static::$_preLoadedCustomData[$customDataSetKey][$key] : null;
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
        static::$_preLoadedCustomData[$customDataSetKey][$identifier] = $value;
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
        static::$_preLoadedCustomData[$customDataSetKey] = static::_preloadIsset($customDataSetKey) ? static::$_preLoadedCustomData[$customDataSetKey] : [];
        $identifiers = $identifiers ?: static::_preloadByColumn();
        foreach ($data as $row) {
            foreach ($identifiers as $identifierColumn) {
                $isNew = !array_key_exists($identifierColumn, static::$_preLoadedCustomData[$customDataSetKey]);
                if ($allowMultiple || $isNew) {
                    $storeValue = $storeSingleColumn ? $row->$storeSingleColumn : $row;
                    if ($isNew) {
                        static::$_preLoadedCustomData[$customDataSetKey][$row->$identifierColumn] = $allowMultiple ? [$storeValue] : $storeValue;
                    } else {
                        static::$_preLoadedCustomData[$customDataSetKey][$row->$identifierColumn][] = $storeValue;
                    }
                }
            }
        }
        if ($customDataSetKey == 'default') {
            static::$_preLoadedData = &static::$_preLoadedCustomData[$customDataSetKey];
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
        return self::all();
    }

}