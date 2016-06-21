<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Core\BlockManager;
use Eloquent;

class Block extends Eloquent
{
    protected $table = 'blocks';
    private static $preloaded_blocks = array();
    private static $repeater_block_ids = array();

    public function languages()
    {
        return $this->hasMany('CoasterCms\Models\PageBlockDefault');
    }

    public static function get_block($block_name)
    {
        return self::where('name', '=', $block_name)->first();
    }

    /**
     * @param $block_name
     * @param bool $force
     * @return \CoasterCms\Models\Block|null
     */
    public static function preload($block_name, $force = false)
    {
        if (empty(self::$preloaded_blocks) || $force) {
            $blocks = self::where('active', '=', 1)->get();
            foreach ($blocks as $block) {
                self::$preloaded_blocks[$block->id] = $block; // load by id as well as name
                self::$preloaded_blocks[$block->name] = $block;
            }
        }
        if (!empty(self::$preloaded_blocks[$block_name]))
            return self::$preloaded_blocks[$block_name];
        else
            return null;
    }

    public static function get_repeater_blocks()
    {
        if (empty(self::$repeater_block_ids)) {
            $repeater_blocks = self::where('type', '=', 'repeater')->get();
            if (!$repeater_blocks->isEmpty()) {
                foreach ($repeater_blocks as $repeater_block) {
                    self::$repeater_block_ids[] = $repeater_block->id;
                }
            }
        }
        return self::$repeater_block_ids;
    }

    public static function get_block_on_page($block_id, $page_id)
    {
        if ($page_id) {
            $page = Page::find($page_id);
            if (!empty($page)) {
                $block_cats = Template::template_blocks(config('coaster::frontend.theme'), $page->template);
            }
        } else {
            $block_cats = Theme::theme_blocks(config('coaster::frontend.theme'));
        }
        if (!empty($block_cats)) {
            foreach ($block_cats as $block_cat) {
                foreach ($block_cat as $block) {
                    if ($block->id == $block_id) {
                        return Block::find($block_id);
                    }
                }
            }
        }
        return null;
    }

    public static function nameToNameArray()
    {
        $array = [];
        foreach (self::all() as $block) {
            $array[$block->name] = $block->name;
        }
        return $array;
    }

    /**
     * @return \CoasterCms\Libraries\Blocks\_Base
     */
    public function get_class()
    {
        $blockClasses = BlockManager::getBlockClasses();
        return !empty($blockClasses[$this->type])?$blockClasses[$this->type]:$blockClasses['string'];
    }

}
