<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Export\Content\PageBlocksExport;
use CoasterCms\Libraries\Export\Content\RepeaterBlocksExport;

class ContentExport extends AbstractExport
{

    /**
     * @var AbstractExport[]
     */
    protected $_contentExportObjects;

    /**
     * @return array
     */
    public function exportUploads()
    {
        $uploads = [];
        foreach ($this->_contentExportObjects as  $exportObject) {
            $uploads = array_merge($exportObject->getUploads(), $uploads);
        }
        return $this->_exportUploads = array_unique($uploads);
    }

    /**
     *
     */
    public function run()
    {
        $exportClasses = [
            PageBlocksExport::class,
            RepeaterBlocksExport::class
        ];
        foreach ($exportClasses as $exportClass) {
            $this->_contentExportObjects[$exportClass] = new $exportClass($this->_exportPath);
            $this->_contentExportObjects[$exportClass]->run();
        }
        $this->exportUploads();
    }

}