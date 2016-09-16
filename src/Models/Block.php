<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;
use Request;

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
        return static::_preloadGetArray($key);
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
        return static::_preloadGetArray('nameToName');
    }

    public static function idToLabelArray()
    {
        static::_preloadOnce(null, 'idToLabel', ['id'], 'label');
        return static::_preloadGetArray('idToLabel');
    }

    /**
     * @return string
     */
    public function get_class()
    {
        $blockClasses = BlockManager::getBlockClasses();
        return !empty($blockClasses[$this->type])?$blockClasses[$this->type]:$blockClasses['string'];
    }

    /**
     * @return \CoasterCms\Libraries\Blocks\String_
     */
    public function getTypeObject()
    {
        $typeClass = $this->getClass();
        return new $typeClass($this);
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return static::getBlockClass($this->type);
    }

    /**
     * @param string $type
     * @param bool $reload
     * @return string
     */
    public static function getBlockClass($type, $reload = false)
    {
        static::_loadClasses($reload);
        return static::_preloadGet('blockClass', $type) ?: static::_preloadGet('blockClass', 'string');
    }

    /**
     * @param bool $reload
     * @return array
     */
    public static function getBlockClasses($reload = false)
    {
        static::_loadClasses($reload);
        return static::_preloadGetArray('blockClass');
    }

    /**
     * @param bool $reload
     */
    protected static function _loadClasses($reload = false)
    {
        if (!static::_preloadIsset('blockClass') || $reload) {
            $paths = [
                'CoasterCms\\Libraries\\Blocks\\' => base_path('vendor/web-feet/coasterframework/src/Libraries/Blocks'),
                'App\\Blocks\\' => base_path('app/Blocks')
            ];

            foreach ($paths as $classPath => $dirPath) {
                if (is_dir($dirPath)) {
                    foreach (scandir($dirPath) as $file) {
                        if ($className = explode('.', $file)[0]) {
                            static::_preloadAdd('blockClass', trim(strtolower($className), '_'), $classPath . $className);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $pageId
     * @param int $versionId
     * @param bool $publish
     */
    public static function submit($pageId, $versionId, $publish = true)
    {
        $submittedBlocks = Request::input('block') ?: [];
        foreach ($submittedBlocks as $blockId => $submittedData) {
            $block = static::preload($blockId);
            //if ($blockId == 27) {dd();}
            if ($block->exists) {
                $blockTypeObject = $block->getTypeObject()->setPageId($pageId)->setVersionId($versionId)->save($submittedData);
                if ($publish) {
                    $blockTypeObject->publish();
                }
            }
        }
    }

}
