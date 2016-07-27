<?php namespace CoasterCms\Models;

use Eloquent;

class BlockFormRule extends Eloquent
{

    protected $table = 'block_form_rules';

    public static function get_rules($template)
    {
        $rules_array = array();
        $rules = self::where('form_template', '=', $template)->get();
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $rules_array[$rule->field] = $rule->rule;
            }
        }
        return $rules_array;
    }

    public static function import($inputRules)
    {
        if (!empty($inputRules)) {

            $databaseRules = [];
            $rules = self::all();

            if (!$rules->isEmpty()) {
                foreach ($rules as $rule) {
                    if (!isset($databaseRules[$rule->form_template])) {
                        $databaseRules[$rule->form_template] = [];
                    }
                    $databaseRules[$rule->form_template][$rule->field] = $rule;
                }
            }

            foreach ($inputRules as $template => $fields) {

                $databaseTemplateRules = !empty($databaseRules[$template])?$databaseRules[$template]:[];

                $toAdd = array_diff_key($fields, $databaseTemplateRules);
                $toUpdate = array_intersect_key($fields, $databaseTemplateRules);
                $toDelete = array_diff_key($databaseTemplateRules, $fields);

                if (!empty($toDelete)) {
                    self::where('form_template', '=', $template)->whereIn('field', array_keys($toDelete))->delete();
                }

                if (!empty($toAdd)) {
                    foreach ($toAdd as $field => $rule) {
                        $newFormRule = new self;
                        $newFormRule->form_template = $template;
                        $newFormRule->field = $field;
                        $newFormRule->rule = $rule;
                        $newFormRule->save();
                    }
                }

                if (!empty($toUpdate)) {
                    foreach ($toUpdate as $field => $rule) {
                        if ($rule != $databaseTemplateRules[$field]->rule) {
                            $databaseTemplateRules[$field]->rule = $rule;
                            $databaseTemplateRules[$field]->save();
                        }
                    }
                }

            }

        }
    }

}