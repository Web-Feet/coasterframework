<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;
use Illuminate\Support\Facades\DB;

class ContentImport extends AbstractImport
{

    /**
     *
     */
    public function run()
    {
        // wipe data
        DB::table((new PageBlockDefault)->getTable())->truncate();
        DB::table((new PageBlock)->getTable())->truncate();
        DB::table((new PageBlockRepeaterData)->getTable())->truncate();
        DB::table((new PageBlockRepeaterRows)->getTable())->truncate();

        Block::preload('', true); // fix block_id issue on install import

        $pageBlocks = new Content\PageBlocksImport($this->_importPath, $this->_importFileRequired);
        $pageBlocks->run();
        $this->_importErrors = array_merge($this->_importErrors, $pageBlocks->getErrors());
        $repeaterBlocks = new Content\RepeaterBlocksImport($this->_importPath, $this->_importFileRequired);
        $repeaterBlocks->run();
        $this->_importErrors = array_merge($this->_importErrors, $repeaterBlocks->getErrors());
    }

}