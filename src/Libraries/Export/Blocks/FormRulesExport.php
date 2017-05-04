<?php namespace CoasterCms\Libraries\Export\Blocks;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\BlockFormRule;

class FormRulesExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = BlockFormRule::class;

}