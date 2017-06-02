<?php namespace CoasterCms\Libraries\Export\Blocks;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\BlockSelectOption;

class SelectOptionExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = BlockSelectOption::class;

}