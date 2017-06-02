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
     *
     */
    const IMPORT_FILE_DEFAULT = 'blocks/form_rules.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Form Template' => [
                'mapTo' => 'form_template',
                'validate' => 'required'
                ],
            'Field' => [
                'mapTo' => 'field',
                'validate' => 'required'
                ],
            'Rule' => [
                'mapTo' => 'rule',
                'validate' => 'required'
            ]
        ];
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $this->_formTemplateRules = [];
        $this->_formTemplateRulesToDelete = [];
        $existingRules = BlockFormRule::all();
        if (!$existingRules->isEmpty()) {
            foreach ($existingRules as $existingRule) {
                if (!array_key_exists($existingRule->form_template, $this->_formTemplateRules)) {
                    $this->_formTemplateRules[$existingRule->form_template] = [];
                }
                $this->_formTemplateRules[$existingRule->form_template][$existingRule->field] = $existingRule;
            }
        }
    }

    /**
     *
     */
    protected function _beforeRowMap()
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
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentFormRule->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentFormRule->save();
    }

    /**
     * When adding form rules clear all previous rules for any conflicting form_templates
     */
    protected function _afterRun()
    {
        foreach ($this->_formTemplateRulesToDelete as $template => $fields) {
            BlockFormRule::where('form_template', '=', $template)->whereIn('field', array_keys($fields))->delete();
        }
    }

    /**
     * @return array
     */
    public function getFormTemplates()
    {
        return array_keys($this->_formTemplateRules);
    }

}