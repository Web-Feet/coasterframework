<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Theme;
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
     * @var bool
     */
    protected $_importedData;

    /**
     * @var array
     */
    protected $_importCurrentRow;

    /**
     * @var array
     */
    protected $_additionalData;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = '';

    /**
     * AbstractImport constructor.
     * @param string $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile = '', $requiredFile = false)
    {
        $this->_importFile = $importFile;
        $this->_importFileRequired = $requiredFile;
        $this->_importedData = false;
        $this->_importData = $this->_importData();
        $this->_fieldMap = $this->fieldMap();
        $this->_hasBeenValidated = false;
        $this->_validationErrors = [];
        $this->_validationRules = $this->validateRules();
        $this->_customValidationColumnNames =$this->_customValidationColumnNames();
    }

    /**
     * @param Theme|int $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        if (is_a($theme, Theme::class)) {
            $this->_additionalData['theme'] = $theme;
        } elseif (is_int($theme)) {
            $this->_additionalData['theme'] = Theme::find($theme);
        } else {
            $this->_additionalData['theme'] = Theme::where(['theme' => $theme])->first();
        }
        if (!$this->_importedData && $this->_additionalData['theme']) {
            $this->_importFile = base_path('resources/views/themes/' . $this->_additionalData['theme']->theme . '/import/' . static::IMPORT_FILE_DEFAULT);
            $this->_importData = $this->_importData();
        }
        return $this;
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
     * @return array
     */
    public function getImportData()
    {
        return $this->_importData;
    }

    /**
     * @param $importData
     */
    public function setImportData($importData)
    {
        $this->_importData = $importData;
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
        if (file_exists($this->_importFile) && !is_dir($this->_importFile) && is_readable($this->_importFile)) {
            $importData = array_map('str_getcsv', file($this->_importFile));
            $headerRow = array_shift($importData);
            try {
                array_walk($importData, ['self', '_csvNameColumns'], $headerRow);
                $this->_importedData = true;
                return $importData;
            } catch (\Exception $e) {
                $this->_validationErrors[] = 'CSV format error, number of columns in the header row does not match for some data rows';
                return false;
            }
        }
        return [];
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
        return array_map(function($field) {
            return array_key_exists('validate', $field) ? $field['validate'] : '';
        }, $this->fieldMap());
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
        if ($this->_importData && $requiredRules = $this->_getRequiredColumns()) {
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

    /**
     * @return bool
     */
    public function run()
    {
        if ($this->validate()) {
            $this->_beforeRun();
            foreach ($this->_importData as $importRow) {
                $this->_importCurrentRow = $importRow;
                $this->_beforeRowMap();
                foreach ($this->_fieldMap as $importFieldName => $importInfo) {
                    $importFieldData = array_key_exists($importFieldName, $importRow) ? $importRow[$importFieldName] : '';
                    $importFieldData = $this->_mapFn($importInfo, $importFieldData);
                    $importFieldData = $this->_setDefaultIfBlank($importInfo, $importFieldData);
                    if (array_key_exists('mapTo', $importInfo)) {
                        $this->_mapTo($importInfo, $importFieldData);
                    }
                }
                $this->_afterRowMap();
            }
            $this->_afterRun();
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    protected function _beforeRun()
    {
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     * @return string
     */
    protected function _setDefaultIfBlank($importInfo, $importFieldData)
    {
        if ($importFieldData === '' && array_key_exists('default', $importInfo)) {
            return is_callable($importInfo['default']) ? call_user_func($importInfo['default']) : $importInfo['default'];
        }
        return $importFieldData;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     * @return string
     */
    protected function _mapFn($importInfo, $importFieldData)
    {
        if (array_key_exists('mapFn', $importInfo)) {
            if ($importInfo['mapFn']) {
                return $this->{$importInfo['mapFn']}($importFieldData);
            }
            return $importFieldData;
        }
        return $this->_mapDefaultFn($importFieldData);
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _mapDefaultFn($importFieldData)
    {
        return trim($importFieldData);
    }

    /**
     * @param string $importColumn
     * @param string $importFieldData
     */
    protected function _mapTo($importColumn, $importFieldData)
    {
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
    }

    /**
     *
     */
    protected function _afterRun()
    {
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toLower($importFieldData)
    {
        return strtolower($importFieldData);
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toLowerTrim($importFieldData)
    {
        return strtolower(trim($importFieldData));
    }

    /**
     * @param string $importFieldData
     * @return bool|null
     */
    protected function _toBool($importFieldData)
    {
        if ($importFieldData !== '') {
            return (empty($importFieldData) || strtolower($importFieldData) == 'true' || strtolower($importFieldData) == 'yes' || strtolower($importFieldData) == 'y');
        }
        return null;
    }

}