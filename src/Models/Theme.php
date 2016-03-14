<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

Class Theme extends Eloquent
{
    protected $table = 'themes';

    public function templates()
    {
        return $this->hasMany('CoasterCms\Models\Template');
    }

    public function blocks()
    {
        return $this->belongsToMany('CoasterCms\Models\Block', 'theme_blocks')->withPivot('show_in_pages', 'exclude_templates', 'show_in_global')->where('active', '=', 1)->orderBy('order', 'asc');
    }

    public static function get_template_list($includeTemplate = 0)
    {
        $templates = array();
        $theme = self::find(config('coaster::frontend.theme'));
        if (!empty($theme)) {
            foreach ($theme->templates()->where('hidden', '=', 0)->get() as $template) {
                $templates[$template->id] = !empty($template->label) ? $template->label : $template->template;
                $templateFile[$template->template] = $template->id;
            }
        }
        // fix template issues on theme switching
        if (!empty($includeTemplate) && empty($templates[$includeTemplate])) {
            $includeTemplateModel = Template::find($includeTemplate);
            if (empty($includeTemplateModel)) {
                $templates[$includeTemplate] = 'Non existent template';
            } elseif (!empty($templateFile[$includeTemplateModel->template])) {
                $templates[$includeTemplate] = $templates[$templateFile[$includeTemplateModel->template]].' (using closest match in current theme)';
            } else {
                $templates[$includeTemplate] = (!empty($includeTemplateModel->label) ? $includeTemplateModel->label : $includeTemplateModel->template).' (not found in current theme - frontend won\'t load)';
            }
        }
        asort($templates);
        return $templates;
    }

    public static function theme_blocks($theme, $template = 0)
    {
        $in_pages = !empty($template) ? true : false;
        $blocks = array();
        $selected_theme = self::find($theme);
        if (!empty($selected_theme)) {
            $theme_blocks = $selected_theme->blocks()->get();
            foreach ($theme_blocks as $theme_block) {
                if ($in_pages && !empty($theme_block->pivot->exclude_templates)) {
                    $ex_templates = explode(",", $theme_block->pivot->exclude_templates);
                    if (!empty($ex_templates) && in_array($template, $ex_templates)) {
                        $theme_block->pivot->show_in_pages = 0;
                    }
                }
                if ((!$in_pages && $theme_block->pivot->show_in_global == 1) || ($in_pages && $theme_block->pivot->show_in_pages == 1)) {
                    if (!isset($blocks[$theme_block->category_id])) {
                        $blocks[$theme_block->category_id] = array();
                    }
                    $blocks[$theme_block->category_id][$theme_block->id] = $theme_block;
                }
            }
        }
        return $blocks;
    }

    public static function selectArray()
    {
        $array = [];
        foreach (self::all() as $theme) {
            $array[$theme->id] = $theme->theme;
        }
        return $array;
    }

}