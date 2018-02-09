<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\App;
use Request;

class Block extends Eloquent
{
    use DataPreLoad;

    /**
     * @var string
     */
    protected $table = 'blocks';

    /**
     * @var int
     */
    protected $_pageId = 0;

    /**
     * @var int
     */
    protected $_repeaterId = 0;

    /**
     * @var int
     */
    protected $_repeaterRowId = 0;

    /**
     * @var int
     */
    protected $_versionId = 0;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function languages()
    {
        return $this->hasMany('CoasterCms\Models\PageBlockDefault');
    }

    /**
     * preload by both id and name
     * @return array
     */
    protected static function _preloadByColumn()
    {
        return ['id', 'name'];
    }

    /**
     * @param $blockId
     * @param $pageId
     * @return static|null
     */
    public static function getBlockOnPage($blockId, $pageId)
    {
        if ($page = Page::find($pageId)) {
            $blocksByCat = ThemeTemplate::templateBlocks(config('coaster::frontend.theme'), $page->template);
        } else {
            $blocksByCat = Theme::theme_blocks(config('coaster::frontend.theme'));
        }
        foreach ($blocksByCat as $blocks) {
            foreach ($blocks as $block) {
                if ($block->id == $blockId) {
                    return static::find($blockId);
                }
            }
        }
        return null;
    }

    /**
     * @param string $blockType
     * @return array
     */
    public static function getBlockIdsOfType($blockType)
    {
        $key = 'type'. ucwords($blockType) .'Ids';
        if (!static::_preloadIsset($key)) {
            $data = static::where('type', '=', $blockType)->get();
            static::_preloadOnce($data, $key, ['id'], 'id');
        }
        return static::_preloadGetArray($key);
    }

    /**
     * @return array
     */
    public static function nameToNameArray()
    {
        static::_preloadOnce(null, 'nameToName', ['name'], 'name');
        return static::_preloadGetArray('nameToName');
    }

    /**
     * @return array
     */
    public static function idToLabelArray()
    {
        static::_preloadOnce(null, 'idToLabel', ['id'], 'label');
        return static::_preloadGetArray('idToLabel');
    }

    /**
     * @return \CoasterCms\Libraries\Blocks\String_
     */
    public function getTypeObject()
    {
        $typeClass = $this->getClass();
        return new $typeClass($this);
    }

    /**
     * @param string $blockClass
     * @param bool $reload
     * @return string
     */
    public static function getBlockType($blockClass, $reload = false)
    {
        static::_loadClasses($reload);
        return array_search($blockClass, static::_preloadGetArray('blockClass')) ?: 'string';
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return static::getBlockClass($this->type);
    }

    /**
     * @param string $type
     * @param bool $reload
     * @return string
     */
    public static function getBlockClass($type, $reload = false)
    {
        static::_loadClasses($reload);
        return static::_preloadGet('blockClass', $type) ?: static::_preloadGet('blockClass', 'string');
    }

    /**
     * @param bool $reload
     * @return array
     */
    public static function getBlockClasses($reload = false)
    {
        static::_loadClasses($reload);
        return static::_preloadGetArray('blockClass');
    }

    /**
     * @param bool $reload
     */
    protected static function _loadClasses($reload = false)
    {
        if (!static::_preloadIsset('blockClass') || $reload) {
            $paths = [
                'CoasterCms\\Libraries\\Blocks\\' => realpath(COASTER_ROOT . '/src/Libraries/Blocks'),
                'App\\Blocks\\' => base_path('app/Blocks')
            ];

            foreach ($paths as $classPath => $dirPath) {
                if (is_dir($dirPath)) {
                    foreach (scandir($dirPath) as $file) {
                        if ($className = explode('.', $file)[0]) {
                            if (stripos($className, 'abstract') === false) {
                                static::_preloadAdd('blockClass', trim(strtolower($className), '_'), $classPath . $className);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $pageId
     * @param int $versionId
     * @param bool $publish
     */
    public static function submit($pageId, $versionId, $publish = true)
    {
        $submittedBlocks = Request::input('block') ?: [];
        foreach ($submittedBlocks as $blockId => $submittedData) {
            $block = static::preloadClone($blockId);
            if ($block->exists) {
                $blockTypeObject = $block->setPageId($pageId)->setVersionId($versionId)->getTypeObject()->submit($submittedData);
                if ($publish) {
                    $blockTypeObject->publish();
                }
            }
        }
    }

    /**
     * @param int $versionId
     * @return $this
     */
    public function setVersionId($versionId)
    {
        $this->_versionId = $versionId;
        return $this;
    }

    /**
     * @param int $pageId
     * @return $this
     */
    public function setPageId($pageId)
    {
        $this->_pageId = $pageId;
        return $this;
    }

    /**
     * @param int $repeaterId
     * @param int $rowId
     * @return $this
     */
    public function setRepeaterData($repeaterId, $rowId)
    {
        $this->_repeaterId = $repeaterId;
        $this->_repeaterRowId = $rowId;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersionId()
    {
        return $this->_versionId;
    }

    /**
     * @return int
     */
    public function getPageId()
    {
        return $this->_pageId;
    }

    /**
     * @return int
     */
    public function getRepeaterId()
    {
        return $this->_repeaterId;
    }

    /**
     * @return int
     */
    public function getRepeaterRowId()
    {
        return $this->_repeaterRowId;
    }

    /**
     * @param string $content
     */
    public function updateContent($content)
    {
        if (!$this->_versionId) {
            $this->_versionId = PageVersion::add_new($this->_pageId)->version_id;
        }
        if ($this->_repeaterId && $this->_repeaterRowId) {
            PageBlockRepeaterData::updateBlockData($content, $this->id, $this->_versionId, $this->_repeaterId, $this->_repeaterRowId);
        } elseif ($this->_pageId) {
            PageBlock::updateBlockData($content, $this->id, $this->_versionId, $this->_pageId);
        } else {
            PageBlockDefault::updateBlockData($content, $this->id, $this->_versionId);
        }
    }

    /**
     * @param bool $globalFallback
     * @return string|null
     */
    public function getContent($globalFallback = false)
    {
        if ($this->_repeaterId && $this->_repeaterRowId) {
            $blockData = PageBlockRepeaterData::getBlockData($this->id, $this->_versionId, $this->_repeaterId, $this->_repeaterRowId);
        } elseif ($this->_pageId) {
            $blockData = PageBlock::getBlockData($this->id, $this->_versionId, $this->_pageId);
        } else {
            $globalFallback = true;
            $blockData = null;
        }
        if ($globalFallback && is_null($blockData)) {
            $blockData = PageBlockDefault::getBlockData($this->id, 0);
        }
        return $blockData;
    }

    /**
     * @param string $searchText
     * @return bool
     */
    public function publishContent($searchText)
    {
        if ($this->_pageId) {
            if (!$this->_repeaterId) {
                PageSearchData::updateText($searchText, $this->id, $this->_pageId);
            }
            $pageVersion = PageVersion::getLiveVersion($this->_pageId);
            if ($this->_versionId != $pageVersion->version_id) {
                return $pageVersion->publish();
            }
        }
        return false;
    }

    /**
     * @param Eloquent $model
     * @param int $version (select specific version or 0 for latest version (-1 available on page_blocks to get live version))
     * @param array $filters
     * @param array $orderBys
     * @return Collection
     * @throws \Exception
     */
    public static function getDataForVersion($model, $version, $filters = [], $orderBys = [])
    {
        $tableName = $model->getTable();
        $fullTableName = DB::getTablePrefix() . $tableName;
        $pageLangTable = DB::getTablePrefix() . (new PageLang)->getTable();

        switch ($tableName) {
            case 'page_blocks': $identifiers = ['block_id', 'language_id', 'page_id']; break;
            case 'page_blocks_default': $identifiers = ['block_id', 'language_id']; break;
            case 'page_blocks_repeater_data': $identifiers = ['block_id', 'row_key']; break;
            default: throw new \Exception('unknown blocks data table: ' . $fullTableName);
        }
        $selectIdentifiers = 'maxVersions.' . implode(', maxVersions.', $identifiers);
        $joinClauses = ['version' => 'version'] + $identifiers;

        $useMaxLiveJoin = ($tableName == 'page_blocks' && $version == -1) ? 'JOIN '.$pageLangTable.' pl ON pl.page_id = maxVersions.page_id AND pl.language_id = maxVersions.language_id AND pl.live_version >= maxVersions.version' : '';

        $whereQueries = ['main' => [], 'maxVersions' => []];
        $whereBindings = ['main' => [], 'maxVersions' => []];
        foreach ($filters as $filterOn => $filterValue) {
            if (!is_null($filterValue) && $filterValue !== '') {
                if (is_array($filterValue)) {
                    $bindings = $filterValue;
                    $eq = 'IN (' . implode(', ', array_fill(0, count($bindings), '?')) . ')';
                } else {
                    $bindings = [$filterValue];
                    $eq = '= ?';
                }
                foreach ($whereQueries as $queryIdentifier => $query) {
                    $whereQueries[$queryIdentifier][] = $queryIdentifier . '.' . $filterOn . ' ' . $eq;
                    $whereBindings[$queryIdentifier] = array_merge($whereBindings[$queryIdentifier], $bindings);
                }
            }
        }
        $whereQueries['main'][] = 'main.content != ""';
        if ($version > 0) {
            $whereQueries['maxVersions'][] = 'version <= ?';
            $whereBindings['maxVersions'][] = $version;
        }

        // add db prefix for raw where sql
        foreach ($whereQueries['main'] as $k => $mainWhereQuery) {
            $whereQueries['main'][$k] = DB::getTablePrefix() . $mainWhereQuery;
        }

        $modelDataVersionQuery = $model::from($tableName . ' as main')
            ->join(
                DB::raw(
                    '(SELECT ' . $selectIdentifiers . ', MAX(maxVersions.version) as version FROM ' . $fullTableName . ' maxVersions '
                    . $useMaxLiveJoin . ' '
                    . (!empty($whereQueries['maxVersions']) ? 'WHERE ' . implode(' AND ', $whereQueries['maxVersions']) : '') . '
                     GROUP BY ' . $selectIdentifiers . ')' . DB::getTablePrefix() . 'j'
                ),
                function (JoinClause $join) use($joinClauses) {
                    foreach ($joinClauses as $joinClauseIdentifier) {
                        $join->on('main.'.$joinClauseIdentifier, '=', 'j.'.$joinClauseIdentifier);
                    }
                })
            ->whereRaw(!empty($whereQueries['main']) ? implode(' AND ', $whereQueries['main']) : '')
            ->setBindings(array_merge($whereBindings['maxVersions'], $whereBindings['main']));

        $orderBys = $orderBys ?: ['block_id' => 'ASC'];
        foreach ($orderBys as $orderBy => $orderDirection) {
            $modelDataVersionQuery->orderBy('main.' . $orderBy, $orderDirection);
        }

        return $modelDataVersionQuery->get(['main.*']);
    }

    /**
     * @param array $categorisedBlocks
     * @param array $blockContents
     * @param int $pageId
     * @param int $versionId
     * @return array
     */
    public static function getTabs($categorisedBlocks, $blockContents, $pageId = 0, $versionId = 0)
    {
        $tabHeaders = [];
        $tabContents = [];
        foreach ($categorisedBlocks as $categoryId => $categoryBlocks) {
            $category = BlockCategory::preload($categoryId);
            $tabIndex = $category->order;
            while (!empty($tabHeaders[$tabIndex])) {
                $tabIndex++;
            }
            $tabHeaders[$tabIndex] = $category->name;
            $tabContents[$tabIndex] = '';
            foreach ($categoryBlocks as $blockId => $block) {
                /** @var Block $block */
                $blockContent = isset($blockContents[$blockId][Language::current()]) ? $blockContents[$blockId][Language::current()]->content : '';
                $tabContents[$tabIndex] .= $block->setPageId($pageId)->setVersionId($versionId)->getTypeObject()->edit($blockContent);
            }
        }
        return [$tabHeaders, $tabContents];
    }

}
