<?php namespace CoasterCms\Libraries\Export\Menus;

use CoasterCms\Libraries\Export\AbstractExport;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;

class MenuItemsExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = MenuItem::class;

    /**
     * @param string $data
     * @return string
     */
    protected function _convertToMenuId($data)
    {
        return Menu::preload($data)->name;
    }

}