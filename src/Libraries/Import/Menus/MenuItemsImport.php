<?php namespace CoasterCms\Libraries\Import\Menus;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use Illuminate\Database\Eloquent\Collection;

class MenuItemsImport extends AbstractImport
{
    /**
     * @var array
     */
    protected $_pageIds;

    /**
     * @var Collection
     */
    protected $_menusByName;

    /**
     * @var MenuItem
     */
    protected $_currentMenuItem;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/menu_items.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Menu Identifier' => [
                'mapTo' => 'menu_id',
                'mapFn' => '_convertToMenuId',
                'validate' => 'required'
            ],
            'Item Page Id' => [
                'mapTo' => 'page_id',
                'validate' => 'required'
            ],
            'Item Order' => [
                'mapTo' => 'order',
                'default' => 0
            ],
            'Item Sublevels' => [
                'mapTo' => 'sub_levels',
                'default' => 0
            ],
            'Item Custom Name' => [
                'mapTo' => 'custom_name'
            ],
            'Item Page Names' => [
                'mapTo' => 'custom_page_names',
                'mapFn' => '_toSerializedArray'
            ],
            'Item Hidden Pages' => [
                'mapTo' => 'hidden_pages',
                'mapFn' => '_checkPageIds'
            ],
        ];
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $this->_menusByName = Menu::get()->keyBy('name');
        $this->_pageIds = Page::get(['id'])->pluck('id')->toArray();
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentMenuItem = new MenuItem;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentMenuItem->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentMenuItem->save();
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _checkPageIds($importFieldData)
    {
        if ($importFieldData !== '') {
            $validPageIds = [];
            $pageIds = explode(',', $importFieldData);
            foreach ($pageIds as $pageId) {
                if(in_array(trim($pageId),  $this->_pageIds)) {
                    $validPageIds[] = trim($pageId);
                }
            }
            return implode(',', $validPageIds);
        }
        return $importFieldData;
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toSerializedArray($importFieldData)
    {
        if ($importFieldData !== '') {
            $validPages = [];
            $pages = json_decode($importFieldData, true);
            foreach ($pages as $pageId => $pageName) {
                if (in_array(trim($pageId), $this->_pageIds)) {
                    $validPages[trim($pageId)] = trim($pageName);
                }
            }
            $importFieldData = parent::_toSerializedArray(json_encode($validPages));;
        }
        return $importFieldData;
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _convertToMenuId($importFieldData)
    {
        if ($importFieldData !== '' && $this->_menusByName->has($importFieldData)) {
            return $this->_menusByName[$importFieldData]->id;
        }
        return '';
    }


}