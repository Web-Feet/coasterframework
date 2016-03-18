<?php namespace CoasterCms\Models;

class BlockFormRule extends _BaseEloquent
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

}