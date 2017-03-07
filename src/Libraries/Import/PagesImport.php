<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Template;

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
     * @var PageGroupPage[]
     */
    protected $_newPageGroupPages;

    /**
     * @var array
     */
    protected $_templateIds;

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Page Id' => ['Page', 'id'],
            'Page Name' => ['PageLang', 'name'],
            'Page Url' => ['PageLang', 'url'],
            'Page Live Version' => ['PageLang', 'live_version'],
            'Page Language Id' => ['PageLang', 'language_id'],
            'Page Template' => '_setTemplate',
            'Parent Page Id' => ['Page', 'parent'],
            'Default Child Template' => ['Page', 'child_template'],
            'Page Order Value' => ['Page', 'order'],
            'Is Link (0 or 1)' => ['Page', 'link'],
            'Is Live (0 or 1)' => ['Page', 'live'],
            'In Sitemap (0 or 1)' => ['Page', 'sitemap'],
            'Container for Group Id' => ['Page', 'group_container'],
            'Container Url Priority' => ['Page', 'group_container_url_priority'],
            'Canonical Parent Page Id' => ['Page', 'canonical_parent'],
            'Group Ids (Comma Separated)' => '_addGroups'
        ];
    }

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Page Name' => 'required',
            'Page Url'  => 'required'
        ];
    }

    /**
     * @return array
     */
    public function defaultsIfBlank()
    {
        return [
            'Page Language Id' => config('coaster::frontend.language'),
            'Page Live Version' => 1,
            'Parent Page Id' => 0,
            'Default Child Template' => 0,
            'Page Order Value'  => 1000,
            'Is Link (0 or 1)'  => 0,
            'Is Live (0 or 1)' => 1,
            'In Sitemap (0 or 1)' => 1,
            'Container for Group Id'  => 0,
            'Container Url Priority'  => 0,
            'Canonical Parent Page Id' => 0
        ];
    }

    public function run()
    {
        $this->_templateIds = [];
        $templates = Template::where('theme_id', '=', $this->_additionalData['theme']->id)->get();
        foreach ($templates as $template) {
            $this->_templateIds[$template->template] = $template->id;
        }
        return parent::run();
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
                list($model, $attribute) = $mappedName;
                $this->{'_current'.$model}->$attribute = $importFieldData;
            } else {
                $this->$mappedName($importFieldData);
            }
        }
    }

    /**
     * @param $templateName
     */
    protected function _setTemplate($templateName)
    {
        $this->_currentPage->template = array_key_exists($templateName, $this->_templateIds) ? $this->_templateIds[$templateName] : config('coaster::admin.default_template');
    }

    /**
     * @param $groupIds
     */
    protected function _addGroups($groupIds)
    {
        $groupIds = $groupIds ? explode(',', $groupIds) : [];
        foreach ($groupIds as $groupId) {
            $newPageGroupPage = new PageGroupPage;
            $newPageGroupPage->group_id = $groupId;
            $this->_newPageGroupPages[] = $newPageGroupPage;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $this->_currentPage = new Page;
        $this->_currentPageLang = new PageLang;
        $this->_newPageGroupPages = [];
    }

    /**
     *
     */
    protected function _endRowImport()
    {
        $this->_currentPage->save();
        $this->_currentPageLang->page_id = $this->_currentPage->id;
        $this->_currentPageLang->save();
        foreach ($this->_newPageGroupPages as $newPageGroupPage) {
            $newPageGroupPage->page_id = $this->_currentPage->id;
            $newPageGroupPage->save();
        }
        PageVersion::add_new($this->_currentPage->id);
    }

}