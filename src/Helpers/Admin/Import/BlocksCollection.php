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
            'csv' => 100,
            'file' => 50,
            'db' => 20,
            'guess' => 10
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
        return $this->_blocks[$blockName][$scope] = new Block($blockData);
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
            $this->_blocks[$blockName][$scope] = new Block();
        }
        return $this->_blocks[$blockName][$scope];
    }

    /**
     * @param string $blockName
     * @param array $scopes
     * @return Block
     */
    public function getAggregatedBlock($blockName, $scopes = [])
    {
        $aggregatedData = new Block();
        if (array_key_exists($blockName, $this->_blocks)) {
            $scopes = $this->_getScopes($scopes);
            arsort($scopes);
            foreach ($scopes as $scope => $scopeOrder) {
                if (array_key_exists($scope, $this->_blocks[$blockName])) {
                    $aggregatedData->combine($this->_blocks[$blockName][$scope]);
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
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    /**
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

}