<?php namespace CoasterCms\Libraries\Export\Blocks;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\BlockCategory;

class CategoryExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = BlockCategory::class;

}