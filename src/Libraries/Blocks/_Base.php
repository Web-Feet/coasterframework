<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\PageBlock;

class _Base
{

    /**
     * @var string
     */
    public static $blocks_key = 'block';

    /**
     * @var boolean
     */
    public static $edit_display_raw = false;

    /**
     * @var array
     */
    public static $edit_id = array();

    /**
     * @var array
     */
    public static $extra_data = array();

    /**
     * @param \CoasterCms\Models\Block $block
     * @param string $block_data
     * @param array $options
     * @return string
     */
    public static function display($block, $block_data, $options = array())
    {
        return $block_data;
    }

    /**
     * @param \CoasterCms\Models\Block $block
     * @param string $block_data
     * @param int $page_id
     * @return string
     */
    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        self::$edit_id = array($block->id);
        return $block_data;
    }

    /**
     * @param int $page_id
     * @param string $blocks_key
     * @param \stdClass $repeater_info
     */
    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {

    }

    /**
     * @param string $block_content
     * @return string
     */
    public static function save($block_content)
    {
        return $block_content;
    }

    /**
     * @param string $block_content
     * @param int $version
     * @return null|string
     */
    public static function search_text($block_content, $version = 0)
    {
        $data = @unserialize($block_content);
        if ($block_content === 'b:0;' || $data !== false) {
            return null; // serialized data should have custom function
        } else {
            return strip_tags($block_content);
        }
    }

    /**
     * @param int $block_id
     * @param string $search
     * @param string $type
     * @return array
     */
    public static function filter($block_id, $search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($block_id);
        $page_ids = array();
        if (!empty($live_blocks)) {
            foreach ($live_blocks as $live_block) {
                switch ($type) {
                    case '=':
                        if ($live_block->content == $search) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        if (strpos($live_block->content, $search) !== false) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

    /**
     * @param \CoasterCms\Models\Block $block
     * @param string $block_data
     * @return array
     */
    public static function exportFiles($block, $block_data)
    {
        return [];
    }

    /**
     * @return array
     */
    public static function block_settings_action()
    {
        return ['action' => '', 'name' => ''];
    }

}
