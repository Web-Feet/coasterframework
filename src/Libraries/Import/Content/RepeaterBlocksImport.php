<?php namespace CoasterCms\Libraries\Import\Content;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;

class RepeaterBlocksImport extends AbstractImport
{
    /**
     * @var PageBlockRepeaterData
     */
    protected $_currentRepeaterBlock;

    /**
     * @var PageBlockRepeaterRows
     */
    protected $_currentRepeaterRow;

    /**
     * @var array
     */
    protected $_repeaterKeys;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/repeater_blocks.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Repeater Id' => [
                'mapTo' => ['PageBlockRepeaterRows', 'repeater_id'],
                'validate' => 'required'
            ],
            'Repeater Row' => [
                'mapTo' => ['PageBlockRepeaterRows', 'row_id'],
                'validate' => 'required'
            ],
            'Block Name' => [
                'mapTo' => ['PageBlockRepeaterData', 'block_id'],
                'mapFn' => '_toBlockId',
                'validate' => 'present'
            ],
            'Content' => [
                'mapTo' => ['PageBlockRepeaterData', 'content'],
                'default' => ''
            ],
            'Version Id' => [
                'mapTo' => ['PageBlockRepeaterData', 'version'],
                'default' => 1
            ]
        ];
    }

    protected function _beforeRun()
    {
        $rows = PageBlockRepeaterRows::get();
        foreach ($rows as $row) {
            $this->_repeaterKeys[$row->repeater_id.':'.$row->row_id] = $row->id;
        }
        $this->_repeaterKeys = [];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentRepeaterBlock = new PageBlockRepeaterData;
        $this->_currentRepeaterRow = new PageBlockRepeaterRows;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        list($model, $attribute) = $importInfo['mapTo'];
        if ($model == 'PageBlockRepeaterData') {
            $this->_currentRepeaterBlock->$attribute = $importFieldData;
        } else {
            $this->_currentRepeaterRow->$attribute = $importFieldData;
            if ($this->_currentRepeaterRow->row_id && $this->_currentRepeaterRow->repeater_id) {
                $repeaterKey = $this->_currentRepeaterRow->row_id . ':' . $this->_currentRepeaterRow->repeater_id;
                if (!array_key_exists($repeaterKey, $this->_repeaterKeys)) {
                    $this->_currentRepeaterRow->save();
                    $this->_repeaterKeys[$repeaterKey] = $this->_currentRepeaterRow->id;
                }
                $this->_currentRepeaterBlock->row_key = $this->_repeaterKeys[$repeaterKey];
            }
        }
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentRepeaterBlock->save();
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