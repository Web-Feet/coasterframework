<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Models\Block;

class BlocksExport extends AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel = Block::class;

    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldDataFromMapTo($fieldDetails)
    {
        if ($fieldDetails['mapTo'][0] == 'setBlockData') {
            return $this->_currentExportItem->{$fieldDetails['mapTo'][1]};
        } elseif ($fieldDetails['mapTo'][0] == 'setGlobalData') {

        } elseif ($fieldDetails['mapTo'][0] == 'addTemplates') {

        }
        return '';
    }

}