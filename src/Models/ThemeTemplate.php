<?php namespace CoasterCms\Models;

use Eloquent;

class ThemeTemplate extends Eloquent
{

    /**
     * @var string
     */
    protected $table = 'theme_templates';

    /**
     * @return mixed
     */
    public function blocks()
    {
        return $this->hasMany('CoasterCms\Models\ThemeTemplateBlock');
    }

    /**
     * @param int $themeId
     * @param int $templateId
     * @return array
     */
    public static function templateBlocks($themeId, $templateId)
    {
        $blocks = Theme::theme_blocks($themeId, $templateId);
        $themeTemplates = static::where('theme_id', '=', $themeId)->where('template_id', '=', $templateId)->with('blocks')->first();
        if (!empty($themeTemplates)) {
            foreach ($themeTemplates->blocks as $themeTemplateBlocks) {
                $block = Block::preload($themeTemplateBlocks->block_id);
                if ($block->exists) {
                    if (!array_key_exists($block->category_id, $blocks)) {
                        $blocks[$block->category_id] = [];
                    }
                    $blocks[$block->category_id][$block->id] = $block;
                }
            }
        }
        // order theme/template blocks properly in each category/tab
        foreach ($blocks as $categoryId => $block) {
            uasort($blocks[$categoryId], ['self', 'order_blocks']);
        }
        return $blocks;
    }

    /**
     * @param Block $a
     * @param Block $b
     * @return int
     */
    private static function order_blocks($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

}