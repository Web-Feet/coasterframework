<?php namespace CoasterCms\Libraries\Export\Menus;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Libraries\Import\Menus\MenuItemsImport;
use CoasterCms\Models\MenuItem;

class MenuItemsExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportClass = MenuItem::class;

    /**
     * @var string
     */
    protected $_importClass = MenuItemsImport::class;

}