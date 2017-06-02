<?php namespace CoasterCms\Libraries\Export\Groups;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\PageGroupAttribute;

class GroupAttributesExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = PageGroupAttribute::class;

}