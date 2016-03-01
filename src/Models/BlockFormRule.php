<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

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

}