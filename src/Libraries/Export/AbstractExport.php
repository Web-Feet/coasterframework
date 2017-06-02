<?php namespace CoasterCms\Libraries\Export;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\Template;

abstract class AbstractExport
{

    /**
     * @var string
     */
    protected $_exportModel;

    /**
     * @var object
     */
    protected $_currentExportItem;

    /**
     * @var array
     */
    protected $_exportHeader = [];

    /**
     * @var array
     */
    protected $_exportData = [];

    /**
     * @var array
     */
    protected $_exportUploads = [];

    /**
     * @var string
     */
    protected $_exportFile;

    /**
     * @var string
     */
    protected $_exportPath;

    /**
     * @var string
     */
    protected $_importClass;

    /**
     * @var AbstractImport
     */
    protected $_importObject;

    /**
     * @var array
     */
    protected $_importFieldMap = [];

    /**
     * AbstractExport constructor.
     * @param string $exportPath
     * @param bool $isDir
     */
    public function __construct($exportPath = '', $isDir = true)
    {
        $this->_importClass = $this->_importClass ?: str_replace('Export', 'Import', static::class);
        $this->_importObject = new $this->_importClass;
        $this->_importFieldMap = $this->_importObject->fieldMap() ?: [];
        $this->_exportPath = $exportPath;
        $this->_exportFile = $isDir ? rtrim($this->_exportPath, '/') . '/' . constant($this->_importClass.'::IMPORT_FILE_DEFAULT') : $this->_exportPath;
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
        return isset($this->_exportModel) ? call_user_func([$this->_exportModel, 'all']) : [];
    }

    /**
     *
     */
    protected function _extractData()
    {
        $this->_exportHeader = array_keys($this->_importFieldMap);
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
        fputcsv($csvHandle, $this->_exportHeader);
         if (method_exists($this, '_orderData')) {
             usort($this->_exportData, [$this, '_orderData']);
         }
        foreach ($this->_exportData as $rowData) {
            fputcsv($csvHandle, $rowData);
        }
        fclose($csvHandle);
    }

    /**
     * @return array
     */
    public function getUploads()
    {
        return array_unique($this->_exportUploads);
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _mapTemplate($data)
    {
        return Template::preload($data)->template;
    }

    /**
     * Reverse of the import func so should return json
     * @param $data
     * @return string
     */
    protected function _toSerializedArray($data)
    {
        if ($array = @unserialize($data)) {
            return json_encode($array);
        }
        return '';
    }

    /**
     * @param string $data
     * @return string
     */
    protected function _toBlockId($data)
    {
        return Block::preload($data)->name;
    }

}