<?php namespace CoasterCms\Libraries\Export\Content;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;

class PageBlocksExport extends AbstractExport
{

    /**
     * @return object[]
     */
    protected function _loadModelData()
    {
        $pageBlocks = collect(array_merge(Block::getDataForVersion(new PageBlock, 0)->all(), Block::getDataForVersion(new PageBlockDefault, 0)->all()));

        foreach ($pageBlocks as $k => $pageBlock) {
            $block = Block::preload($pageBlock->block_id);
            if ($block->exists) {
                $this->_exportUploads = array_merge($block->getTypeObject()->exportFiles($pageBlock->content), $this->_exportUploads);
            } else {
                $pageBlocks->forget($k);
            }
        }

        return $pageBlocks;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _orderData($a, $b)
    {
        if ($a[0] == $b[0]) {
            return strcmp($a[1], $b[1]);
        }
        return $a[0] < $b[0] ? -1 : 1;
    }

}