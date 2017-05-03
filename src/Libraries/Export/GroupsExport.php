<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Export\Groups\GroupAttributesExport;
use CoasterCms\Models\PageGroup;

class GroupsExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = PageGroup::class;

    /**
     *
     */
    public function run()
    {
        parent::run();
        $itemsExport = new GroupAttributesExport($this->_exportPath);
        $itemsExport->run();
    }

}