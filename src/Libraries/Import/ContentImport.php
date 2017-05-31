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
     * ContentImport constructor.
     * @param string $importPath
     * @param bool $requiredFile
     */
    public function __construct($importPath = '', $requiredFile = false)
    {
        parent::__construct($importPath, $requiredFile);
        $childClasses = [
            Content\PageBlocksImport::class,
            Content\RepeaterBlocksImport::class
        ];
        $this->setChildren($childClasses);
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        // wipe data
        DB::table((new PageBlockDefault)->getTable())->truncate();
        DB::table((new PageBlock)->getTable())->truncate();
        DB::table((new PageBlockRepeaterData)->getTable())->truncate();
        DB::table((new PageBlockRepeaterRows)->getTable())->truncate();

        Block::preload('', true); // fix block_id issue on install import
    }

}