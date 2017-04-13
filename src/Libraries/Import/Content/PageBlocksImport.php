<?php namespace CoasterCms\Libraries\Import\Content;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;

class PageBlocksImport extends AbstractImport
{
    /**
     * @var PageBlock
     */
    protected $_currentPageBlock;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/page_blocks.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Page Id' => [
                'mapTo' => 'page_id',
                'validate' => 'required'
            ],
            'Block Name' => [
                'mapTo' => 'block_id',
                'mapFn' => '_toBlockId',
                'validate' => 'required'
            ],
            'Content' => [
                'mapTo' => 'content',
                'default' => ''
            ],
            'Language Id' => [
                'mapTo' => 'language_id',
                'default' => config('coaster::frontend.language')
            ],
            'Version Id' => [
                'mapTo' => 'version',
                'default' => 1
            ]
        ];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentPageBlock = $this->_importCurrentRow['Page Id'] ? new PageBlock : new PageBlockDefault;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        if ($this->_importCurrentRow['Page Id'] || $importInfo['mapTo'] != 'page_id') {
            $this->_currentPageBlock->{$importInfo['mapTo']} = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentPageBlock->save();
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