<?php namespace CoasterCms\Libraries\Import;


class ContentImport extends AbstractImport
{

    /**
     *
     */
    public function run()
    {
        $pageBlocks = new Content\PageBlocksImport();
        $pageBlocks->run();
        $repeaterBlocks = new Content\RepeaterBlocksImport();
        $repeaterBlocks->run();
    }

}