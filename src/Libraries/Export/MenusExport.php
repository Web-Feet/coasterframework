<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Export\Menus\MenuItemsExport;
use CoasterCms\Models\Menu;

class MenusExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = Menu::class;

    /**
     *
     */
    public function run()
    {
        parent::run();
        $itemsExport = new MenuItemsExport($this->_exportPath);
        $itemsExport->run();
    }

}