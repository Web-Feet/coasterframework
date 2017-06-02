<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;

class PagesExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = Page::class;

    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldDataFromMapTo($fieldDetails)
    {
        if ($fieldDetails['mapTo'][0] == 'PageLang') {
            return PageLang::preload($this->_currentExportItem->id)->{$fieldDetails['mapTo'][1]};
        } else {
            return $this->_currentExportItem->{$fieldDetails['mapTo'][1]};
        }
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _mapGroups($data)
    {
        return implode(',', PageGroupPage::getGroupIds($this->_currentExportItem->id));
    }

}