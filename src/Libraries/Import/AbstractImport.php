<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\Theme;
use Illuminate\Validation\ValidationException;
use Validator;

abstract class AbstractImport
{

    /**
     * @var AbstractImport[]
     */
    protected $_children;

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
    protected $_customValidationColumnNames;

    /**
     * @var bool
     */
    protected $_hasBeenValidated;

    /**
     * @var \Exception[]
     */
    protected $_importErrors;

    /**
     * @var string
     */
    protected $_importPath;

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
     * @param string $importPath
     * @param bool $requiredFile
     */
    public function __construct($importPath = '', $requiredFile = false)
    {
        $this->_importErrors = [];
        if (file_exists($importPath)) {
            $this->_importFile = rtrim($importPath, '/') . (is_dir($importPath) ? '/' . static::IMPORT_FILE_DEFAULT : '');
        } else {
            $this->_importFile = '';
        }
        $this->_importPath = $importPath;
        $this->_importFileRequired = $requiredFile;
        $this->_importedData = false;
        $this->_importData = $this->_importData();
        $this->_fieldMap = $this->fieldMap();
        $this->_hasBeenValidated = false;
        $this->_validationRules = $this->validateRules();
        $this->_customValidationColumnNames = $this->_customValidationColumnNames();
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
        $importData = [];
        if (file_exists($this->_importFile) && !is_dir($this->_importFile) && is_readable($this->_importFile)) {
            if (($handle = fopen($this->_importFile, 'r')) !== false) {
                $headerRow = [];
                while (($importRow = fgetcsv($handle, 1000, ',')) !== false) {
                    if (!$headerRow) {
                        $headerRow = $importRow;
                    } else {
                        $importData[] = array_combine($headerRow, $importRow);
                    }
                }
                fclose($handle);
                $this->_importedData = true;
            }
        }
        return $importData;
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
            if ($this->_importFileRequired && $this->_importedData === false) {
                $this->_importErrors[] = new \Exception('A required import file is missing or not readable: ' . $this->_importFile);
                return false;
            }
            if ($this->_validationRules) {
                // do basic validation on column first (if fails then skip row checks for that column)
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
                                $this->_importErrors[] = new \Exception('Data row ' . ($row + 1) . ': ' . $errorMessage);
                            }
                        }
                    }
                }
            }
            $this->_hasBeenValidated = empty($this->_importErrors);
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
                    $this->_importErrors[] = new \Exception($presentMessage[0]);
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
    public function getErrors()
    {
        return $this->_importErrors;
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return array_map(function (\Exception $e) {return $e->getMessage();}, $this->_importErrors);
    }

    /**
     * @return bool
     */
    public function run()
    {
        if ($this->validate()) {
            try {
                $this->_beforeRun();
                foreach ($this->_importData as $importRow) {
                    try {
                        $this->_importCurrentRow = $importRow;
                        $this->_beforeRowMap();
                        foreach ($this->_fieldMap as $importFieldName => $importInfo) {
                            $tryFieldNames = array_merge([$importFieldName], array_key_exists('aliases', $importInfo) ? $importInfo['aliases'] : []);
                            $importFieldData = '';
                            foreach ($tryFieldNames as $tryFieldName) {
                                if (array_key_exists($tryFieldName, $importRow)) {
                                    $importFieldData = $importRow[$tryFieldName];
                                    break;
                                }
                            }
                            $importFieldData = $this->_mapFn($importInfo, $importFieldData);
                            $importFieldData = $this->_setDefaultIfBlank($importInfo, $importFieldData);
                            if (array_key_exists('mapTo', $importInfo)) {
                                $this->_mapTo($importInfo, $importFieldData);
                            }
                        }
                        $this->_afterRowMap();
                    } catch (\Exception $e) {
                        $this->_importErrors[] = $e;
                    }
                }
                $this->_afterRun();
            } catch (\Exception $e) {
                $this->_importErrors[] = $e;
            }
            if ($this->_children) {
                foreach ($this->_children as $child) {
                    $child->run();
                    $this->_importErrors = array_merge($this->_importErrors, $child->getErrors());
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $childClasses
     */
    public function setChildren($childClasses = [])
    {
        foreach ($childClasses as $childClass) {
            $this->_children[] = new $childClass($this->_importPath, $this->_importFileRequired);
        }
    }

    /**
     *
     */
    public function deleteCsv()
    {
        if ($this->_children) {
            foreach ($this->_children as $child) {
                $child->deleteCsv();
            }
        }
        if ($this->_importFile && file_exists($this->_importFile) && !is_dir($this->_importFile)) {
            unlink($this->_importFile);
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
     * @return bool|string
     */
    protected function _toBool($importFieldData)
    {
        if ($importFieldData !== '') {
            return (empty($importFieldData) || strtolower($importFieldData) == 'true' || strtolower($importFieldData) == 'yes' || strtolower($importFieldData) == 'y');
        }
        return '';
    }

    /**
     * @param string $importFieldData
     * @return int|string
     */
    protected function _toBoolInt($importFieldData)
    {
        if ($importFieldData !== '') {
            return $this->_toBool($importFieldData) ? 1 : 0;
        }
        return '';
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toSerializedArray($importFieldData)
    {
        if ($importFieldData !== '') {
            if ($array = json_decode($importFieldData, true)) {
                return serialize($array);
            }
        }
        return '';
    }

}