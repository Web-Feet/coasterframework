<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockSelectOption;

class SelectOptionImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockSelectOptions;

    /**
     * @var BlockSelectOption
     */
    protected $_currentSelectOption;

    /**
     * @var array
     */
    protected $_blockNamesToIds;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'blocks/select_options.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => [
                'mapTo' => 'block_id',
                'mapFn' => '_toBlockId',
                'validate' => 'required'
                ],
            'Option' => [
                'mapTo' => 'option',
                'validate' => 'present'
                ],
            'Value' => [
                'mapTo' => 'value',
                'validate' => 'present'
            ]
        ];
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if ($isValid = parent::validate()) {
            foreach ($this->_importData as $rowData) {
                $block = Block::preload($rowData['Block Name']);
                if (!$block->exists) {
                    $this->_importErrors[] = new \Exception('Block not found under name: '. $rowData['Block Name']);
                    $isValid = false;
                } else {
                    $this->_blockNamesToIds[$block->name] = $block->id;
                }
            }
            return $isValid;
        }
        return false;
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $this->_blockSelectOptions = [];
        $existingOptions = BlockSelectOption::all();
        if (!$existingOptions->isEmpty()) {
            foreach ($existingOptions as $existingOption) {
                if (!array_key_exists($existingOption->block_id, $this->_blockSelectOptions)) {
                    $this->_blockSelectOptions[$existingOption->block_id] = [];
                }
                $this->_blockSelectOptions[$existingOption->block_id][$existingOption->value] = $existingOption;
            }
        }
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $blockId = $this->_blockNamesToIds[trim($this->_importCurrentRow['Block Name'])];
        $value = trim($this->_importCurrentRow['Value']);
        if (!array_key_exists($blockId, $this->_blockSelectOptions)) {
            $this->_blockSelectOptions[$blockId] = [];
        }
        if (!array_key_exists($value, $this->_blockSelectOptions[$blockId])) {
            $this->_blockSelectOptions[$blockId][$value] = new BlockSelectOption;
        }
        $this->_currentSelectOption = $this->_blockSelectOptions[$blockId][$value];
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentSelectOption->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toBlockId($importFieldData)
    {
        return $this->_blockNamesToIds[$importFieldData];
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentSelectOption->save();
    }

    /**
     * Can be run before import without validation checks (also returns existing db entries)
     * @return array
     */
    public function getSelectBlockNames()
    {
        $importBlockNames = array_map(function ($data) {return array_key_exists('Block Name', $data) ? $data['Block Name'] : '';}, $this->_importData);
        $importBlockNames = array_filter($importBlockNames);
        $currentBlockNames = BlockSelectOption::blockNamesWithOptions()->toArray();
        return array_unique(array_merge($importBlockNames, $currentBlockNames));
    }

}