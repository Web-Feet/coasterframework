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
     * @var array
     */
    protected $_customValidationColumnNames;

    /**
     * @var bool
     */
    protected $_hasBeenValidated;

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
     * @var array
     */
    protected $_additionalData;

    /**
     * AbstractImport constructor.
     * @param string $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile, $requiredFile = true)
    {
        $this->_importFile = $importFile;
        $this->_importFileRequired = $requiredFile;
        $this->_importData = $this->_importData();
        $this->_fieldMap = $this->fieldMap();
        $this->_hasBeenValidated = false;
        $this->_validationErrors = [];
        $this->_validationRules = $this->validateRules();
        $this->_customValidationColumnNames =$this->_customValidationColumnNames();
        $this->_defaultsIfBlank = $this->defaultsIfBlank();
    }

    /**
     *
     */
    public function getAdditionalData()
    {
        return $this->_additionalData;
    }

    /**
     * @param $data
     */
    public function setAdditionalData($data)
    {
        $this->_additionalData = $data;
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
    protected function _customValidationColumnNames()
    {
        $columnHeaders = array_keys($this->_fieldMap);
        return array_combine($columnHeaders, array_map(function ($columnHeader) {return '\''.$columnHeader.'\'';}, $columnHeaders));
    }

    /**
     * @return array|bool
     */
    protected function _importData()
    {
        if (file_exists($this->_importFile) && is_readable($this->_importFile)) {
            $importData = array_map('str_getcsv', file($this->_importFile));
            $headerRow = array_shift($importData);
            try {
                array_walk($importData, ['self', '_csvNameColumns'], $headerRow);
                return $importData;
            } catch (\Exception $e) {
                $this->_validationErrors[] = 'CSV format error, number of columns in the header row does not match for some data rows';
            }
        }
        return false;
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

    /**
     * @return bool
     */
    public function run()
    {
        if ($this->validate()) {
            foreach ($this->_importData as $importRow) {
                $this->_importCurrentRow = $importRow;
                $this->_startRowImport();
                foreach ($this->_fieldMap as $importFieldName => $importTo) {
                    $importFieldData = array_key_exists($importFieldName, $importRow) ? $importRow[$importFieldName] : '';
                    $importFieldData = ($importFieldData === '' && array_key_exists($importFieldName, $this->_defaultsIfBlank)) ? $this->_defaultsIfBlank[$importFieldName] : $importFieldData;
                    $this->_importField($importFieldName, $importFieldData);
                }
                $this->_endRowImport();
            }
            return true;
        } else {
            return false;
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
        if (!$this->_hasBeenValidated) {
            if ($this->_importFileRequired && $this->_importData === false) {
                $this->_validationErrors[] = 'A required import file is missing or not readable: ' . $this->_importFile;
                return false;
            }
            if ($this->_validationRules) {
                // do column checks first to reduce number of overall checks
                $skipFieldValidation = $this->_validateColumns();
                foreach ($this->_importData as $row => $importRow) {
                    try {
                        $rules = $this->_validationRules;
                        foreach ($skipFieldValidation as $skipField) {
                            unset($importRow[$skipField]);
                            unset($rules[$skipField]);
                        }
                        Validator::make($importRow, $rules, [], $this->_customValidationColumnNames)->validate();
                    } catch (ValidationException $e) {
                        foreach ($e->validator->getMessageBag()->toArray() as $errorMessages) {
                            foreach ($errorMessages as $errorMessage) {
                                $this->_validationErrors[] = 'Data row ' . ($row + 1) . ': ' . $errorMessage;
                            }
                        }
                    }
                }
            }
            $this->_hasBeenValidated = empty($this->_validationErrors);
        }
        return $this->_hasBeenValidated;
    }

    /**
     * @return array
     */
    protected function _validateColumns()
    {
        if ($requiredRules = $this->_getRequiredColumns()) {
            $firstDataRow = current($this->_importData) ?: [];
            try {
                Validator::make($firstDataRow, $requiredRules, [], $this->_customValidationColumnNames)->validate();
            } catch (ValidationException $e) {
                $errorBag = $e->validator->getMessageBag();
                foreach ($errorBag->toArray() as $presentMessage) {
                    $this->_validationErrors[] = $presentMessage[0];
                }
                return $errorBag->keys();
            }
        }
        return [];
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