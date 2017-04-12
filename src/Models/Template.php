<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use DB;
use Eloquent;

class Template extends Eloquent
{
    use DataPreLoad;

    protected $table = 'templates';
    protected static $_blocksOfType = [];

    /**
     * @return array
     */
    protected static function _preloadByColumn()
    {
        return ['id', 'template'];
    }

    public static function name($template_id)
    {
        return self::preload($template_id)->template;
    }

    public static function getTemplateIds($templates) {
        $validTemplates = [];
        foreach ($templates as $templateIdentifier) {
            $template = static::preload($templateIdentifier);
            if ($template->exists) {
                $validTemplates[$template->id] = $template->id;
            }
        }
        return $validTemplates;
    }

    public static function blocks_of_type($type)
    {
        $numb_type = array();
        $blockIds = Block::getBlockIdsOfType($type);
        if (!empty($blockIds)) {
            $sw = ThemeBlock::where('theme_id', '=', config('coaster::frontend.theme'))
                ->whereIn('block_id', $blockIds)->where('show_in_pages', '=', 1)->count();
            $themeTemplates = ThemeTemplate::where('theme_id', '=', config('coaster::frontend.theme'))
                ->with(['blocks' => function ($q) use($blockIds) { $q->whereIn('block_id', $blockIds); }])->get();
            foreach ($themeTemplates as $themeTemplate) {
                $numb_type[$themeTemplate->template_id] = $sw + $themeTemplate->blocks->count();
            }
        }
        return $numb_type;
    }

    public static function preload_blocks_of_type($type, $templateId = null)
    {
        if (!isset(self::$_blocksOfType[$type])) {
            self::$_blocksOfType[$type] = self::blocks_of_type($type);
        }
        if ($templateId !== null) {
            return !empty(self::$_blocksOfType[$type][$templateId])?self::$_blocksOfType[$type][$templateId]:0;
        } else {
            return self::$_blocksOfType[$type];
        }
    }

}