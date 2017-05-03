<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Import\GroupsImport;
use CoasterCms\Models\PageGroup;

class GroupsExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportClass = PageGroup::class;

    /**
     * @var string
     */
    protected $_importClass = GroupsImport::class;

}