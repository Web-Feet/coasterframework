<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\BlockFormRule;

class FormRulesImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_formTemplateRules;

    /**
     * @var BlockFormRule
     */
    protected $_currentFormRule;

    /**
     * @var array
     */
    protected $_formTemplateRulesToDelete;

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Form Template' => 'required',
            'Field' => 'required',
            'Rule' => 'required'
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Form Template' => 'form_template',
            'Field' => 'field',
            'Rule' => 'rule'
        ];
    }

    /**
     * When adding form rules clear all previous rules for any conflicting form_templates
     * @return bool
     */
    public function run()
    {
        $this->_loadExisting();
        $this->_formTemplateRulesToDelete = [];
        if ($hasRun = parent::run()) {
            foreach ($this->_formTemplateRulesToDelete as $template => $fields) {
                self::where('form_template', '=', $template)->whereIn('field', array_keys($fields))->delete();
            }
        }
        return $hasRun;
    }

    /**
     *
     */
    protected function _loadExisting()
    {
        $existingRules = BlockFormRule::all();
        if (!$existingRules->isEmpty()) {
            foreach ($existingRules as $existingRule) {
                if (!array_key_exists($existingRule->form_template, $this->_formTemplateRules)) {
                    $this->_formTemplateRules[$existingRule->form_template] = [];
                }
                $this->_formTemplateRules[$existingRule->form_template][$existingRule->field] = $existingRule;
            }
        } else {
            $this->_formTemplateRules = [];
        }
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
            $this->_currentFormRule->$mappedName = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $formTemplate = trim($this->_importCurrentRow['Form Template']);
        $formField = trim($this->_importCurrentRow['Field']);
        if (!array_key_exists($formTemplate, $this->_formTemplateRules)) {
            $this->_formTemplateRules[$formTemplate] = [];
        }
        if (!array_key_exists($formField, $this->_formTemplateRules[$formTemplate])) {
            $this->_formTemplateRules[$formTemplate][$formField] = new BlockFormRule;
        }
        $this->_currentFormRule = $this->_formTemplateRules[$formTemplate][$formField];
        if (!array_key_exists($formTemplate, $this->_formTemplateRulesToDelete)) {
            $this->_formTemplateRulesToDelete[$formTemplate] = $this->_formTemplateRules[$formTemplate];
        }
        unset($this->_formTemplateRulesToDelete[$formTemplate][$formField]);
    }

    /**
     *
     */
    protected function _endRowImport()
    {
        $this->_currentFormRule->save();
    }

    /**
     * @return array
     */
    public function getFormTemplates()
    {
        return array_keys($this->_formTemplateRules);
    }

}