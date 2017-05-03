<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Export\Content\PageBlocksExport;
use CoasterCms\Libraries\Export\Content\RepeaterBlocksExport;

class ContentExport extends AbstractExport
{

    /**
     *
     */
    public function run()
    {
        $itemsExport = new PageBlocksExport($this->_exportPath);
        $itemsExport->run();
        $itemsExport = new RepeaterBlocksExport($this->_exportPath);
        $itemsExport->run();
    }

}