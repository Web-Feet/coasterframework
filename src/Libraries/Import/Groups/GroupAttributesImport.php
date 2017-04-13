<?php namespace CoasterCms\Libraries\Import\Groups;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageGroupAttribute;
use CoasterCms\Models\Template;

class GroupAttributesImport extends AbstractImport
{
    /**
     * @var PageGroupAttribute
     */
    protected $_currentGroupAttribute;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/group_attributes.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Attribute Id' => [
                'mapTo' => 'id',
                'validate' => 'required'
            ],
            'Group Id' => [
                'mapTo' => 'group_id',
                'validate' => 'required'
            ],
            'Block Name' => [
                'mapTo' => 'item_block_id',
                'mapFn' => '_toBlockId',
                'validate' => 'required'
            ],
            'Order Priority' => [
                'mapTo' => 'item_block_order_priority',
                'aliases' => ['Order Priority (0 for no ordering)']
            ],
            'Order Dir' => [
                'mapTo' => 'item_block_order_dir',
                'aliases' => ['Order Dir (asc/desc)']
            ],
            'Container Filter by Block Name' => [
                'mapTo' => 'filter_by_block_id',
                'mapFn' => '_toBlockId'
            ]
        ];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentGroupAttribute = new PageGroupAttribute;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentGroupAttribute->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentGroupAttribute->save();
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toBlockId($importFieldData)
    {
        if ($importFieldData !== '') {
            $block = Block::preload(trim($importFieldData));
            if ($block->exists) {
                return $block->id;
            }
        }
        return 0;
    }

}