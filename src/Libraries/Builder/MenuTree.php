<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Libraries\Builder\ViewClasses\MenuItemDetails;
use Illuminate\Support\Arr;

class MenuTree
{

    /**
     * @var array
     */
    protected $_currentKey = [];

    /**
     * @var array
     */
    protected $_tree = [];

    /**
     * @var MenuItemDetails[]
     */
    protected $_itemData;

    /**
     * @param string $key
     */
    public function downLevel($key)
    {
        array_push($this->_currentKey, $key);
    }

    /**
     *
     */
    public function upLevel()
    {
        array_pop($this->_currentKey);
    }

    /**
     * @param MenuItemDetails $item
     */
    public function add($item)
    {
        $key = implode('.', array_merge($this->_currentKey, [$item->item->page_id]));
        $this->_tree = Arr::add($this->_tree, $key, []);
        $this->_itemData[$key] = $item;
    }

    /**
     * MenuTree constructor.
     * @param array $itemData
     * @param array $currentKey
     * @param null $tree
     */
    public function __construct(&$itemData = [], $currentKey = [], &$tree = null)
    {
        $this->_itemData = &$itemData;
        $this->_currentKey = $currentKey;
        $this->_tree = &$tree;
    }

    /**
     * @param string $pageId
     * @return static
     */
    public function newInstance($pageId)
    {
        return new static($this->_itemData, array_merge($this->_currentKey, [$pageId]), $this->_tree);
    }

    /**
     * @param bool $parent
     * @return string
     */
    public function getKey($parent = false)
    {
        $key = $parent ? array_slice($this->_currentKey, 0, -1) : $this->_currentKey;
        return $key ? implode('.', $key) : null;
    }

    /**
     * @return array
     */
    public function getTree()
    {
        return $this->_tree;
    }

    /**
     * @return array|mixed|null
     */
    public function getCurrentLevelItems()
    {
        $key = $this->getKey(true);
        $itemIds = Arr::get($this->_tree, $key);
        $items = [];
        foreach ($itemIds as $itemId => $value) {
            $itemKey = ($key ? $key . '.' : '') . $itemId;
            $items[$itemKey] = $this->_itemData[$itemKey];
        }
        return $items;
    }

    /**
     * @return array|mixed
     */
    public function getSubItems()
    {
        $key = $this->getKey();
        $itemIds = Arr::get($this->_tree, $key);
        $items = [];
        foreach ($itemIds as $itemId => $value) {
            $itemKey = ($key ? $key . '.' : '') . $itemId;
            $items[$itemKey] = $this->_itemData[$itemKey];
        }
        return $items;
    }

    /**
     * Recursive and flatten
     * @return array|mixed
     */
    public function getFlatSubItems()
    {
        $key = $this->getKey();
        $itemIds = Arr::get($this->_tree, $key);
        $flatItemIds = $this->_dot($itemIds, $key ? $key . '.' : '');
        $items = [];
        foreach ($flatItemIds as $itemId => $value) {
            $items[$itemId] = $this->_itemData[$itemId];
        }
        return $items;
    }

    /**
     * @param $array
     * @param $prepend
     * @return array
     */
    protected function _dot($array, $prepend)
    {
        $results = [];
        foreach ($array as $key => $value) {
            $results[$prepend.$key] = $value;
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, Arr::dot($value, $prepend.$key.'.'));
            }
        }
        return $results;
    }

}


