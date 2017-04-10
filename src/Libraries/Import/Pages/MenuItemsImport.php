<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;

class MenuItemsImport extends AbstractImport
{
    /**
     * @var array
     */
    protected $_pageIds;

    /**
     * @var array
     */
    protected $_menusByName;

    /**
     * @var MenuItem
     */
    protected $_currentMenuItem;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/menus_items.csv';

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
                'mapFn' => '_serializeNames'
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
        $this->_menusByName = Page::get()->keyBy('name');
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
     *
     */
    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _serializeNames($importFieldData)
    {
        if ($importFieldData !== '') {
            $validPages = [];
            $pageNamesData = explode(',', $importFieldData);
            foreach ($pageNamesData as $pageNameData) {
                $pageNameData = explode(':', $pageNameData, 2);
                if (count($pageNameData) == 2) {
                    list($pageId, $pageName )= $pageNameData;
                    if (in_array(trim($pageId), $this->_pageIds)) {
                        $validPages[trim($pageId)] = trim($pageName);
                    }
                }
            }
            $importFieldData = serialize($validPages);
        }
        return $importFieldData;
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _convertToMenuId($importFieldData)
    {
        if ($importFieldData !== '' && array_key_exists($importFieldData, $this->_menusByName)) {
            return $this->_menusByName[$importFieldData]->id;
        }
        return '';
    }


}