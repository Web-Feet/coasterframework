<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Import\MenusImport;
use CoasterCms\Models\Menu;

class MenusExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportClass = Menu::class;

    /**
     * @var string
     */
    protected $_importClass = MenusImport::class;

}