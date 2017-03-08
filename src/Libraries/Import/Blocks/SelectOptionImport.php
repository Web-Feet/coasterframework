<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\PageGroupPage;

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
     * @return array
     */
    public function validateRules()
    {
        return [
            'Block Name' => 'required',
            'Option' => 'required',
            'Value' => 'required'
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => 'id',
            'Option' => 'option',
            'Value' => 'value'
        ];
    }

    /**
     * @return bool
     */
    public function run()
    {
        $this->_loadExisting();
        return parent::run();
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
                    $this->_validationErrors[] = 'Block not found under name: '. $rowData['Block Name'];
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
    protected function _loadExisting()
    {
        $existingOptions = BlockSelectOption::all();
        if (!$existingOptions->isEmpty()) {
            foreach ($existingOptions as $existingOption) {
                if (!array_key_exists($existingOption->block_id, $this->_blockSelectOptions)) {
                    $this->_blockSelectOptions[$existingOption->block_id] = [];
                }
                $this->_blockSelectOptions[$existingOption->block_id][$existingOption->value] = $existingOption;
            }
        } else {
            $this->_currentSelectOption = [];
        }
    }

    /**
     * @param string $importFieldName
     * @param string $importFieldData
     */
    protected function _importField($importFieldName, $importFieldData)
    {
        $importFieldData = trim($importFieldData);
        $mappedName = $this->_fieldMap[$importFieldName];
        if ($importFieldData !== '') {
            if ($mappedName == 'name') {
                $importFieldData = $this->_blockNamesToIds[$importFieldData];
            }
            $this->_blockSelectOptions->$mappedName = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
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
     *
     */
    protected function _endRowImport()
    {
        $this->_currentSelectOption->save();
    }

    /**
     * @return array
     */
    public function getBlockIdsWithOptions()
    {
        return array_keys($this->_blockSelectOptions);
    }

}