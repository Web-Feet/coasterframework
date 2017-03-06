<?php namespace CoasterCms\Libraries\Import;

use Illuminate\Validation\ValidationException;
use Validator;

abstract class AbstractImport
{

    /**
     * @var array
     */
    protected $_fieldMap;

    /**
     * @var array
     */
    protected $_validationRules;

    /**
     * @var array
     */
    protected $_validationErrors;

    /**
     * @var string
     */
    protected $_importFile;


    /**
     * @var bool
     */
    protected $_importFileRequired;

    /**
     * @var array
     */
    protected $_importData;

    /**
     * @var array
     */
    protected $_importCurrentRow;

    /**
     * @var array
     */
    protected $_defaultsIfBlank;

    /**
     * AbstractImport constructor.
     * @param string $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile, $requiredFile = true)
    {
        $this->_importFile = $importFile;
        $this->_importFileRequired = $requiredFile;
        $this->_validationErrors = [];
        $this->_validationRules = $this->validateRules();
        $this->_fieldMap = $this->fieldMap();
        $this->_defaultsIfBlank = $this->defaultsIfBlank();
        if (file_exists($importFile) && is_readable($importFile)) {
            $importData = array_map('str_getcsv', file($importFile));
            $headerRow = array_shift($importData);
            try {
                array_walk($importData, ['self', '_csvNameColumns'], $headerRow);
                $this->_importData = $importData;
            } catch (\Exception $e) {
                $this->_validationErrors[] = 'CSV format error, number of columns in the header row does not match for some data rows';
                $this->_importData = [];
            }
        } else {
            $this->_importData = false;
        }
    }

    /**
     * @param array $row
     * @param int $column
     * @param array $headerRow
     */
    protected function _csvNameColumns(&$row, $column, $headerRow)
    {
        $row = array_combine($headerRow, $row);
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [];
    }

    /**
     * @return array
     */
    public function validateRules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function defaultsIfBlank()
    {
        return [];
    }

    public function run()
    {
        foreach ($this->_importData as $importRow) {
            $this->_importCurrentRow = $importRow;
            $this->_startRowImport();
            foreach ($importRow as $importFieldName => $importFieldData) {
                if (array_key_exists($importFieldName, $this->_fieldMap)) {
                    $importFieldData = ($importFieldData === '' && array_key_exists($importFieldName, $this->_defaultsIfBlank)) ? $this->_defaultsIfBlank[$importFieldName] : $importFieldData;
                    $this->_importField($importFieldName, $importFieldData);
                }
            }
            $this->_endRowImport();
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
    }

    /**
     *
     */
    protected function _endRowImport()
    {
    }

    /**
     * @param string $importColumn
     * @param string $importFieldData
     */
    protected function _importField($importColumn, $importFieldData)
    {
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if ($this->_importFileRequired && $this->_importData === false) {
            $this->_validationErrors[] = 'A required import file is missing or not readable: ' . $this->_importFile;
            return false;
        }

        if ($this->_validationRules) {

            // do column checks once to reduce number
            $skipFieldValidation = [];
            if ($requiredRules = $this->_getRequiredColumns()) {
                $firstDataRow = current($this->_importData) ?: [];
                try {
                    Validator::make($firstDataRow, $requiredRules)->validate();
                } catch (ValidationException $e) {
                    $errorBag = $e->validator->getMessageBag();
                    foreach ($errorBag->toArray() as $presentMessage) {
                        $this->_validationErrors[] = $presentMessage[0];
                    }
                    $skipFieldValidation = $errorBag->keys();
                }
            }

            foreach ($this->_importData as $row => $importRow) {
                try {
                    $rules = $this->_validationRules;
                    foreach ($skipFieldValidation as $skipField) {
                        unset($importRow[$skipField]);
                        unset($rules[$skipField]);
                    }
                    Validator::make($importRow, $rules)->validate();
                } catch (ValidationException $e) {
                    foreach ($e->validator->getMessageBag()->toArray() as $errorMessages) {
                        foreach ($errorMessages as $errorMessage) {
                            $this->_validationErrors[] = 'Data row '.($row+1).': ' . $errorMessage;
                        }
                    }
                }
            }

        }

        return empty($this->_validationErrors);
    }

    /**
     * @return array
     */
    protected function _getRequiredColumns()
    {
        $requiredFields = [];
        foreach ($this->_validationRules as $columnHeader => $rules) {
            if (stripos($rules, 'required') !== false || stripos($rules, 'present') !== false) {
                $requiredFields[$columnHeader] = 'present';
            }
        }
        return $requiredFields;
    }

    /**
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }

}