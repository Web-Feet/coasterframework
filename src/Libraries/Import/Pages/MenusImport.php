<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Menu;

class MenusImport extends AbstractImport
{
    /**
     * @var Menu
     */
    protected $_currentMenu;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/menus.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Menu Identifier' => [
                'mapTo' => 'name',
                'validate' => 'required'
            ],
            'Menu Name' => [
                'mapTo' => 'label',
                'mapFn' => '_setNameIfNull'
            ],
            'Menu Max Sublevels' => [
                'mapTo' => 'max_sublevels',
                'default' => 0
            ],
        ];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentMenu = new Menu;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentMenu->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentMenu->save();
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _setNameIfNull($importFieldData)
    {
        if ($importFieldData !== '') {
            $importFieldData = ucwords(str_replace('_', ' ', $this->_importCurrentRow['Menu Identifier']));
        }
        return $importFieldData;
    }

}