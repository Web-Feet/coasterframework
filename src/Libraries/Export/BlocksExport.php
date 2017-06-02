<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Helpers\Admin\Import\Block;
use CoasterCms\Libraries\Export\Blocks\CategoryExport;
use CoasterCms\Libraries\Export\Blocks\FormRulesExport;
use CoasterCms\Libraries\Export\Blocks\SelectOptionExport;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Theme;

class BlocksExport extends AbstractExport
{

    /**
     * @var Block[]
     */
    protected $_exportModelData;

    /**
     * @var array
     */
    protected $_existingImportData;

    /**
     * Needs to be set so import data can be used in _loadModelData
     * @param Theme|int $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->_importObject->setTheme($theme);
        return $this;
    }

    /**
     * @param Block[] $importBlocks
     * @return $this
     */
    public function setExportData($importBlocks)
    {
        foreach ($importBlocks as $blockName => &$importBlock) {
            if (!array_key_exists('name', $importBlock->blockData)) {
                $importBlock->blockData['name'] = $blockName; // required export field
            }
        }
        $this->_exportModelData = $importBlocks;
        return $this;
    }

    /**
     *
     */
    public function run()
    {
        if (!isset($this->_exportModelData)) {
            $itemsExport = new CategoryExport($this->_exportPath);
            $itemsExport->run();
            $itemsExport = new FormRulesExport($this->_exportPath);
            $itemsExport->run();
            $itemsExport = new SelectOptionExport($this->_exportPath);
            $itemsExport->run();
        }
        parent::run();
    }

    /**
     * @return mixed
     */
    protected function _loadModelData()
    {
        $this->_existingImportData = [];
        foreach ($this->_importObject->getImportData() as $importRow) {
            if (array_key_exists('Block Name', $importRow)) {
                $this->_existingImportData[$importRow['Block Name']] = $importRow;
            }
        }
        return isset($this->_exportModelData) ? $this->_exportModelData : $this->_importObject->getExportCollection();
    }

    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldDataFromMapTo($fieldDetails)
    {
        if (count($fieldDetails['mapTo']) == 2) {
            list($function, $field) = $fieldDetails['mapTo'];
            $property = lcfirst(substr($function, 3));
            return array_key_exists($field, $this->_currentExportItem->$property) ? $this->_currentExportItem->{$property}[$field] : '';
        } else {
            $property = lcfirst(substr($fieldDetails['mapTo'][0], 3));
            return $this->_currentExportItem->$property;
        }
    }

    /**
     * if * used keep it for new file
     * @param array $data
     * @return string
     */
    protected function _mapTemplates($data)
    {
        if (array_key_exists($this->_currentExportItem->blockData['name'], $this->_existingImportData) && array_key_exists('Templates', $this->_existingImportData[$this->_currentExportItem->blockData['name']])) {
            $currentCsvTemplates = $this->_existingImportData[$this->_currentExportItem->blockData['name']]['Templates'];
        } else {
            $currentCsvTemplates = '';
        }
        return in_array('*', explode(',', $currentCsvTemplates)) ? $currentCsvTemplates : implode(',', $data);
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _toCategoryId($data)
    {
        return BlockCategory::preload($data)->name;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _toBoolInt($data)
    {
        return $data !== '' ? ($data ? 'yes' : 'no') : '';
    }

}