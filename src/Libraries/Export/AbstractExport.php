<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Models\Template;

class AbstractExport
{

    /**
     * @var string
     */
    protected $_exportClass = '';

    /**
     * @var object
     */
    protected $_currentExportItem;

    /**
     * @var array
     */
    protected $_exportData = [];

    /**
     * @var string
     */
    protected $_exportFile = '';

    /**
     * @var string
     */
    protected $_exportPath = '';

    /**
     * @var string
     */
    protected $_importClass = '';

    /**
     * @var array
     */
    protected $_importFieldMap = [];

    /**
     * AbstractExport constructor.
     * @param string $exportPath
     */
    public function __construct($exportPath)
    {
        $this->_exportPath = $exportPath;
        if ($this->_importClass) {
            $importObject = new $this->_importClass;
            $this->_importFieldMap = $importObject->fieldMap() ?: [];
            $this->_exportFile = $importObject::IMPORT_FILE_DEFAULT;
        }
        $this->_exportFile = rtrim($this->_exportPath, '/') . '/' . $this->_exportFile;
    }

    /**
     *
     */
    public function run()
    {
        if ($this->_importFieldMap) {
            $this->_extractData();
            $this->saveToCsv();
        }
    }

    /**
     * @return object[]
     */
    protected function _loadModelData()
    {
        $exportClass = $this->_exportClass;
        return $exportClass::all();
    }

    /**
     *
     */
    protected function _extractData()
    {
        $this->_exportData[] = array_keys($this->_importFieldMap);
        $exportModelData = $this->_loadModelData();
        foreach ($exportModelData as $exportItem) {
            $fieldData = [];
            $this->_currentExportItem = $exportItem;
            foreach ($this->_importFieldMap as $fieldName => $fieldDetails) {
                $fieldData[] = $this->_extractFieldData($fieldDetails);
            }
            $this->_exportData[] = $fieldData;
        }
    }

    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldData($fieldDetails)
    {
        $data = '';
        if (array_key_exists('mapTo', $fieldDetails)) {
            $data = $this->_extractFieldDataFromMapTo($fieldDetails);
        }
        if (array_key_exists('mapFn', $fieldDetails) && method_exists($this, $fieldDetails['mapFn'])) {
            $data = $this->{$fieldDetails['mapFn']}($data);
        }
        return $data;
    }

    /**
     * @param array $fieldDetails
     * @return string
     */
    protected function _extractFieldDataFromMapTo($fieldDetails)
    {
        return $this->_currentExportItem->{$fieldDetails['mapTo']};
    }

    /**
     * @param string $exportFile
     */
    public function saveToCsv($exportFile = '')
    {
        $csvFile = $exportFile ?: $this->_exportFile;
        @mkdir(dirName($csvFile), 0777, true);
        $csvHandle = fopen($csvFile, 'w');
        foreach ($this->_exportData as $rowData) {
            fputcsv($csvHandle, $rowData);
        }
        fclose($csvHandle);
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _mapTemplate($data)
    {
        return Template::preload($data)->template;
    }

}