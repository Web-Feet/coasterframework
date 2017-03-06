<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PageVersion;

class PagesImport extends AbstractImport
{
    /**
     * @var Page
     */
    protected $_currentPage;

    /**
     * @var PageLang
     */
    protected $_currentPageLang;

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Page Id' => '',
            'Page Name' => '',
            'Page Url'  => '',
            'Page Template' => '',
            'Page Page Id' => '',
            'Default Child Template' => '',
            'Page Order Value'  => '',
            'Is Link (0 or 1)'  => '',
            'Is Live (0 or 1)' => '',
            'In Sitemap (0 or 1)' => '',
            'Container for Group Id'  => '',
            'Container Url Priority'  => '',
            'Canonical Parent Page Id' => '',
            'Group Ids (Comma Separated)' => ''
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Page Id' => ['Page', 'id'],
            'Page Name' => ['PageLang', 'name'],
            'Page Url' => ['PageLang', 'url'],
            'Page Template' => ['Page', 'template'],
            'Page Page Id' => ['Page', 'parent'],
            'Default Child Template' => ['Page', 'id'],
            'Page Order Value' => ['Page', 'order'],
            'Is Link (0 or 1)' => ['Page', 'link'],
            'Is Live (0 or 1)' => ['Page', 'live'],
            'In Sitemap (0 or 1)' => ['Page', 'sitemap'],
            'Container for Group Id' => ['', 'group_container'],
            'Container Url Priority' => ['Page', 'group_container_url_priority'],
            'Canonical Parent Page Id' => ['Page', 'canonical_parent'],
            'Group Ids (Comma Separated)' => '_addGroups'
        ];
    }

    /**
     * @return array
     */
    public function defaultsIfBlank()
    {
        return [
            'Page Id' => '',
            'Page Name' => '',
            'Page Url'  => '',
            'Page Template' => '',
            'Page Page Id' => '',
            'Default Child Template' => '',
            'Page Order Value'  => '',
            'Is Link (0 or 1)'  => '',
            'Is Live (0 or 1)' => '',
            'In Sitemap (0 or 1)' => '',
            'Container for Group Id'  => '',
            'Container Url Priority'  => '',
            'Canonical Parent Page Id' => '',
            'Group Ids (Comma Separated)' => ''
        ];
    }

    /**
     * @param string $importFieldName
     * @param string $importFieldData
     */
    protected function _importField($importFieldName, $importFieldData)
    {
        $importFieldData = trim($importFieldData);
        $mappedName = $this->_fieldMap[$importFieldName];
        if ($importFieldData !== '') {
            if (is_array($mappedName)) {
                $model = '_current' . $mappedName[0];
                $attribute = $mappedName[1];
                $this->$model->$attribute = $importFieldData;
            } else {
                $this->$mappedName($importFieldData);
            }
        }
    }

    /**
     * @param $groupIds
     */
    protected function _addGroups($groupIds)
    {
        $groupIds = $groupIds ? explode(',', $groupIds) : [];
        foreach ($groupIds as $groupId) {
            $newPageGroupPage = new PageGroupPage;
            $newPageGroupPage->page_id = $this->_importCurrentRow['Page Id'];
            $newPageGroupPage->group_id = $groupId;
            $newPageGroupPage->save();
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $this->_currentPage = new Page;
        $this->_currentPageLang = new PageLang;
    }

    /**
     *
     */
    protected function _endRowImport()
    {
        $this->_currentPage->save();
        $this->_currentPageLang->save();
        PageVersion::add_new($this->_currentPage->id);
    }

}