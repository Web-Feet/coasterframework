<?php namespace CoasterCms\Libraries\Import;

class BlocksImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockSettings;

    /**
     * @var string
     */
    protected $_currentBlockName;

    /**
     * BlocksImport constructor.
     * @param $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile, $requiredFile = true)
    {
        parent::__construct($importFile, $requiredFile);
        $this->_blockSettings = [];
    }

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Block Name' => 'required',
            'Block Label' => '',
            'Block Note' => '',
            'Block Category' => '',
            'Block Type' => '',
            'Global (show in site-wide)' => '',
            'Global (show in pages)' => '',
            'Templates' => '',
            'Block Order' => ''
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => 'name',
            'Block Label' => 'label',
            'Block Note' => 'note',
            'Block Category' => 'category_id',
            'Block Type' => 'type',
            'Global (show in site-wide)' => 'global_site',
            'Global (show in pages)' => 'global_pages',
            'Templates' => 'templates',
            'Block Order' => 'order'
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
            if (in_array($mappedName, ['global_site', 'global_pages'])) {
                $importFieldData = (empty($importFieldData) || strtolower($importFieldData) == 'false' || strtolower($importFieldData) == 'no' || strtolower($importFieldData) == 'n');
            }
            if ($mappedName == 'name') {
                $importFieldData = strtolower($importFieldData);
            }

            $this->_blockSettings[$this->_currentBlockName][$mappedName] = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $this->_currentBlockName = $this->_importCurrentRow['Block Name'];
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->_blockSettings;
    }

}