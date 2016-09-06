<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class Block extends Eloquent
{
    use DataPreLoad;

    protected $table = 'blocks';

    public function languages()
    {
        return $this->hasMany('CoasterCms\Models\PageBlockDefault');
    }

    protected static function _preloadByColumn()
    {
        return ['id', 'name'];
    }

    public static function get_block($block_name)
    {
        return self::where('name', '=', $block_name)->first();
    }

    public static function getBlockIdsOfType($blockType)
    {
        $key = 'type'. ucwords($blockType) .'Ids';
        if (!static::_preloadIsset($key)) {
            $data = static::where('type', '=', $blockType)->get();
            static::_preloadOnce($data, $key, ['id'], 'id');
        }
        return static::_preloadGet($key);
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
        static::_preloadOnce(null, 'nameToName', ['name'], 'name');
        return static::_preloadGet('nameToName');
    }

    public static function idToLabelArray()
    {
        static::_preloadOnce(null, 'idToLabel', ['id'], 'label');
        return static::_preloadGet('idToLabel');
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
