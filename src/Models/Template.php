<?php namespace CoasterCms\Models;

use DB;
use Eloquent;

class Template extends Eloquent
{

    protected $table = 'templates';
    protected static $_blocksOfType = [];

    public static function name($template_id)
    {
        $template = self::find($template_id);
        if (!empty($template)) {
            return $template->template;
        } else {
            return null;
        }
    }

    public function blocks()
    {
        return $this->belongsToMany('CoasterCms\Models\Block', 'template_blocks')->where('active', '=', 1)->orderBy('order', 'asc');
    }

    public static function template_blocks($theme, $template)
    {
        $blocks = Theme::theme_blocks($theme, $template);
        $selected_template = self::find($template);
        if (!empty($selected_template)) {
            $template_blocks = $selected_template->blocks()->get();
            foreach ($template_blocks as $template_block) {
                if (!isset($blocks[$template_block->category_id])) {
                    $blocks[$template_block->category_id] = array();
                }
                $blocks[$template_block->category_id][$template_block->id] = $template_block;
            }
        }

        // order theme/template blocks properly
        foreach ($blocks as $cat_id => $block_cat) {
            uasort($blocks[$cat_id], array('self', 'order_blocks'));
        }

        return $blocks;
    }

    private static function order_blocks($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    public static function blocks_of_type($type)
    {
        $numb_type = array();
        $type_blocks = Block::where('type', '=', $type)->get();
        foreach ($type_blocks as $block) {
            $block_ids[] = $block->id;
        }
        if (!empty($block_ids)) {
            $sw = ThemeBlock::whereIn('block_id', $block_ids)->where('show_in_pages', '=', 1)->count();
            $templates = TemplateBlock::whereIn('block_id', $block_ids)->groupBy('template_id')->get(array('template_id', DB::raw('count(*) as type')));
            foreach ($templates as $template) {
                $numb_type[$template->template_id] = $sw + $template->type;
            }
        }
        return $numb_type;
    }

    public static function preload_blocks_of_type($type, $templateId = null)
    {
        if (!isset(self::$_blocksOfType[$type])) {
            self::$_blocksOfType[$type] = self::blocks_of_type($type);
        }
        if ($templateId) {
            return !empty(self::$_blocksOfType[$type][$templateId])?self::$_blocksOfType[$type][$templateId]:0;
        } else {
            return self::$_blocksOfType[$type];
        }
    }

}