<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\BlockCategory;

class CategoryImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockCategoryByName;

    /**
     * @var BlockCategory
     */
    protected $_currentBlockCategory;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'blocks/categories.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Category' => [
                'mapTo' => 'name',
                'validate' => 'required'
            ],
            'Category Order' => [
                'mapTo' =>'order'
            ]
        ];
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $existingCategories = BlockCategory::all();
        if (!$existingCategories->isEmpty()) {
            foreach ($existingCategories as $category) {
                $this->_blockCategoryByName[strtolower($category->name)] = $category;
            }
        } else {
            $this->_blockCategoryByName = [];
        }
    }
    /**
     *
     */
    protected function _beforeRowMap()
    {
        $categoryName = trim(strtolower($this->_importCurrentRow['Block Category']));
        if (!array_key_exists($categoryName, $this->_blockCategoryByName)) {
            $this->_blockCategoryByName[$categoryName] = new BlockCategory;
        }
        $this->_currentBlockCategory = $this->_blockCategoryByName[$categoryName];
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentBlockCategory->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentBlockCategory->save();
    }

    /**
     * @return array
     */
    public function getBlockCategoriesByName()
    {
        return $this->_blockCategoryByName;
    }

}