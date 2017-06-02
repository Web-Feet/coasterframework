<?php namespace CoasterCms\Libraries\Export\Content;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;

class RepeaterBlocksExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = PageBlockRepeaterData::class;
    
    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldDataFromMapTo($fieldDetails)
    {
        if ($fieldDetails['mapTo'][0] == 'PageBlockRepeaterRows') {
            return PageBlockRepeaterRows::preload($this->_currentExportItem->row_key)->{$fieldDetails['mapTo'][1]};
        } else {
            return $this->_currentExportItem->{$fieldDetails['mapTo'][1]};
        }
    }

    /**
     * @return object[]
     */
    protected function _loadModelData()
    {
        $repeaterIds = [];
        $pageBlocks = collect(array_merge(Block::getDataForVersion(new PageBlock, 0)->all(), Block::getDataForVersion(new PageBlockDefault, 0)->all()));
        foreach ($pageBlocks as $k => $pageBlock) {
            $block = Block::preload($pageBlock->block_id);
            if ($block->exists && strtolower($block->type) == 'repeater') {
                $repeaterIds[] = $pageBlock->content;
            }
        }
        return $this->_loadRepeaterBlocks($repeaterIds);
    }

    /**
     * @param $repeaterIds
     * @return array
     */
    protected function _loadRepeaterBlocks($repeaterIds)
    {
        $repeaterBlocks = [];
        if ($repeaterIds = array_unique($repeaterIds)) {
            $rowKeys = [];
            foreach ($repeaterIds as $repeaterId) {
                $rowKeys = array_merge(array_keys(PageBlockRepeaterRows::getRowIds($repeaterId)), $rowKeys);
            }
            $repeaterBlocks = Block::getDataForVersion(new PageBlockRepeaterData, 0, ['row_key' => $rowKeys]) ?: [];
            foreach ($repeaterBlocks as $k => $repeaterBlock) {
                $block = Block::preload($repeaterBlock->block_id);
                if ($block->exists) {
                    if (strtolower($block->type) == 'repeater') {
                        $subRepeaterIds[] = $repeaterBlock->content;
                    }
                    $this->_exportUploads = array_merge($block->getTypeObject()->exportFiles($repeaterBlock->content), $this->_exportUploads);
                }
            }
            if (!empty($subRepeaterIds)) {
                $repeaterBlocks = $repeaterBlocks->merge($this->_loadRepeaterBlocks($subRepeaterIds));
            }
        }
        return $repeaterBlocks;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _orderData($a, $b)
    {
        if ($a[0] == $b[0]) {
            if ($a[1] == $b[1]) {
                return strcmp($a[2], $b[2]);
            }
            return $a[1] < $b[1] ? -1 : 1;
        }
        return $a[0] < $b[0] ? -1 : 1;
    }

}