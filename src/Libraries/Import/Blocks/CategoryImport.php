<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\BlockCategory;

class CategoryImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockCategoryIds;

    /**
     * @var BlockCategory
     */
    protected $_currentBlockCategory;

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Block Category' => 'required',
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Category' => 'name',
            'Category Order' => 'order'
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
     *
     */
    protected function _loadExisting()
    {
        $existingCategories = BlockCategory::all();
        if (!$existingCategories->isEmpty()) {
            foreach ($existingCategories as $category) {
                $this->_blockCategoryIds[strtolower($category->name)] = $category;
            }
        } else {
            $this->_blockCategoryIds = [];
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
                $importFieldData = strtolower($importFieldData);
            }
            $this->_currentBlockCategory->$mappedName = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $categoryName = trim(strtolower($this->_importCurrentRow['Block Category']));
        if (!array_key_exists($categoryName, $this->_blockCategoryIds)) {
            $this->_blockCategoryIds[$categoryName] = new BlockCategory;
        }
        $this->_currentBlockCategory = $this->_blockCategoryIds[$categoryName];
    }

    /**
     *
     */
    protected function _endRowImport()
    {
        $this->_currentBlockCategory->save();
    }

    /**
     * @return array
     */
    public function getBlockCategoriesByName()
    {
        return $this->_blockCategoryIds;
    }

}