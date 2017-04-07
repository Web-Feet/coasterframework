<?php namespace CoasterCms\Helpers\Admin\Import;

class BlocksCollection
{

    /**
     * @var Block[]
     */
    protected $_blocks;

    /**
     * @var string
     */
    protected $_scope;

    /**
     * @var array
     */
    protected $_scopes;

    /**
     * BlocksCollection constructor.
     */
    public function __construct()
    {
        $this->_blocks = [];
        $this->_scope = 'file';
        $this->_scopes = [
            'form' => 150,
            'csv' => 100,
            'block_' => 60,
            'file' => 50,
            'db' => 10,
            'guess' => 0
        ];
    }

    /**
     * @param string $blockName
     * @param array $blockData
     * @param string $scope
     * @return Block
     */
    public function addBlock($blockName, $blockData = [], $scope = '')
    {
        $scope = $scope ?: $this->_scope;
        if (!array_key_exists($blockName, $this->_blocks)) {
            $this->_blocks[$blockName] = [];
        }
        return $this->_blocks[$blockName][$scope] = new Block($blockData + ['name' => $blockName]);
    }

    /**
     * @param string $blockName
     * @param string $scope
     * @return Block
     */
    public function getBlock($blockName, $scope = '')
    {
        $scope = $scope ?: $this->_scope;
        if (!array_key_exists($blockName, $this->_blocks)) {
            $this->_blocks[$blockName] = [];
        }
        if (!array_key_exists($scope, $this->_blocks[$blockName])) {
            $this->_blocks[$blockName][$scope] = new Block(['name' => $blockName]);
        }
        return $this->_blocks[$blockName][$scope];
    }

    /**
     * @param string $scope
     * @return Block[]
     */
    public function getBlocks($scope = '')
    {
        $blocks = [];
        $scope = $scope ?: $this->_scope;
        foreach ($this->_blocks as $blockName => $blockScopes) {
            if (array_key_exists($scope, $blockScopes)) {
                $blocks[$blockName] = $blockScopes[$scope];
            }
        }
        return $blocks;
    }

    /**
     * Ignores template data from db scope
     * @param string $blockName
     * @param array $scopes
     * @return Block
     */
    public function getAggregatedBlock($blockName, $scopes = [])
    {
        $aggregatedData = new Block(['name' => $blockName]);
        if (array_key_exists($blockName, $this->_blocks)) {
            $scopes = $this->_getScopes($scopes);
            arsort($scopes);
            foreach ($scopes as $scope => $scopeOrder) {
                if (array_key_exists($scope, $this->_blocks[$blockName])) {
                    if ($scope == 'db') {
                        $aggregatedData->setBlockData($this->_blocks[$blockName][$scope]->blockData);
                    } else {
                        $aggregatedData->combine($this->_blocks[$blockName][$scope]);
                    }
                }
            }
        }
        return $aggregatedData;
    }

    /**
     * @param array $scopes
     * @return Block[]
     */
    public function getAggregatedBlocks($scopes = [])
    {
        $aggregatedBlocks = [];
        $scopes = $this->_getScopes($scopes);
        foreach ($this->_blocks as $blockName => $blockScopes) {
            if ($usedScopes = array_intersect_key($blockScopes, $scopes)) {
                $aggregatedBlocks[$blockName] = $this->getAggregatedBlock($blockName, array_keys($usedScopes));
            }
        }
        return $aggregatedBlocks;
    }

    /**
     * @return array
     */
    public function getBlockNames()
    {
        return array_keys($this->_blocks);
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->_scopes;
    }

    /**
     * Return scopes with order values
     * @param array $scopes
     * @return array
     */
    protected function _getScopes($scopes = [])
    {
        if ($scopes) {
            $returnScopes = [];
            foreach ($scopes as $scope) {
                $returnScopes[$scope] = $this->_scopes[$scope];
            }
            return $returnScopes;
        } else {
            return $this->_scopes;
        }
    }

    /**
     * Used in theme review view
     * Caches aggregated blocks as they are heavily used in other functions
     */
    public function getBlockListInfo()
    {
        $blockList = [];
        foreach ($this->getBlockNames() as $blockName) {
            $importBlock = $this->getAggregatedBlock($blockName);
            $currentBlock = $this->getBlock($importBlock->blockData['name'], 'db');
            $blockList[$blockName] = [
                'block_name' => $blockName,
                'display_class' => 'none',
                'update_templates' => -1
            ];
            if ($importBlock->inCategoryTemplates || $importBlock->specifiedPageIds) {
                $blockList[$blockName]['display_class'] = 'info';
            }
            if ($this->hasChanges($importBlock, 'blockData')) {
                $blockList[$blockName]['display_class'] = 'update';
            }
            foreach (['templates', 'repeaterChildBlocks'] as $property) {
                if ($importBlock->$property) {
                    $blockList[$blockName]['update_templates'] = 0;
                }
                if ($this->hasChanges($importBlock, $property)) {
                    $blockList[$blockName]['display_class'] = 'update';
                    $blockList[$blockName]['update_templates'] = 1;
                    break;
                }
            }
            if (!($importBlock->templates || $importBlock->inRepeaterBlocks || $importBlock->inCategoryTemplates || $importBlock->specifiedPageIds)) {
                $blockList[$blockName]['display_class'] = 'delete';
            } elseif (!($currentBlock->templates || $currentBlock->inRepeaterBlocks || $currentBlock->inCategoryTemplates || $currentBlock->specifiedPageIds)) {
                $blockList[$blockName]['display_class'] = ($blockList[$blockName]['display_class'] == 'info') ? 'info' : 'new';
            } elseif ($this->hasChanges($importBlock, 'globalData')) {
                $blockList[$blockName]['display_class'] = 'update';
                $blockList[$blockName]['update_templates'] = 1;
            }
        }
        uasort($blockList, [$this, '_orderBlockChanges']);
        return $blockList;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    protected function _orderBlockChanges($a, $b)
    {
        $byDisplayClass = ['new' => 1, 'update' => 2, 'delete' => 3, 'info' => 4, 'none' => 5];
        if ($byDisplayClass[$a['display_class']] == $byDisplayClass[$b['display_class']]) {
            if ($a['update_templates'] >= 0 && $b['update_templates'] >= 0) {
                return strcmp($a['block_name'], $b['block_name']);
            }
            return ($a['update_templates'] < $b['update_templates']) ? 1 : -1;
        }
        return ($byDisplayClass[$a['display_class']] > $byDisplayClass[$b['display_class']]) ? 1 : -1;
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $compareToScope
     * @return bool
     */
    public function hasChanges($importBlock, $property, $compareToScope = 'db')
    {
        $associativeProperties = ['blockData', 'globalData'];
        if (in_array($property, $associativeProperties)) {
            return (bool) $this->updatedValues($importBlock, $property);
        } else {
            return (bool) $this->changedElements($importBlock, $property, $compareToScope);
        }
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $compareToScope
     * @return array
     */
    public function changedElements($importBlock, $property, $compareToScope = 'db')
    {
        return array_merge($this->newElements($importBlock, $property, $compareToScope), $this->deletedElements($importBlock, $property, $compareToScope));
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $compareToScope
     * @return array
     */
    public function newElements($importBlock, $property, $compareToScope = 'db')
    {
        $currentBlock = $this->getBlock($importBlock->blockData['name'], $compareToScope);
        return array_diff($importBlock->$property, $currentBlock->$property);
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $compareToScope
     * @return array
     */
    public function deletedElements($importBlock, $property, $compareToScope = 'db')
    {
        $currentBlock = $this->getBlock($importBlock->blockData['name'], $compareToScope);
        return array_diff($currentBlock->$property, $importBlock->$property);
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $compareToScope
     * @return array
     */
    public function updatedValues($importBlock, $property, $compareToScope = 'db')
    {
        $currentBlock = $this->getBlock($importBlock->blockData['name'], $compareToScope);
        $updateValues = [];
        foreach ($importBlock->$property as $field => $importValue) {
            $currentValue = array_key_exists($field, $currentBlock->$property) ? $currentBlock->$property[$field] : '';
            if ($currentValue != $importValue) {
                $updateValues[$field] = [
                    'new' => $importValue,
                    'old' => $currentValue
                ];
            }
        }
        foreach ($currentBlock->$property as $field => $currentValue) {
            $importValue = array_key_exists($field, $importBlock->$property) ? $importBlock->$property[$field] : '';
            if ($currentValue != $importValue) {
                $updateValues[$field] = [
                    'new' => $importValue,
                    'old' => $currentValue
                ];
            }
        }
        return $updateValues;
    }

    /**
     * @param Block $importBlock
     * @param string $property
     * @param string $field
     * @param string $compareToScope
     * @return bool
     */
    public function updatedValue($importBlock, $property, $field, $compareToScope = 'db')
    {
        $currentBlock = $this->getBlock($importBlock->blockData['name'], $compareToScope);
        $currentValue = array_key_exists($field, $currentBlock->$property) ? $currentBlock->$property[$field] : '';
        $importValue = array_key_exists($field, $importBlock->$property) ? $importBlock->$property[$field] : '';
        return $currentValue == $importValue ? false : $importValue;
    }

}