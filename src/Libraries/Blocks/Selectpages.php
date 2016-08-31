<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlock;
use View;

class Selectpages extends _Base
{

    public static function display($block, $block_data, $options = null)
    {
        $pages = [];
        $page_ids = [];
        if (isset($options['reverse'])) {
            // get page_ids on which current page is selected in this block
            $currentPageId = PageBuilder::pageId(true);
            if ($currentPageId) {
                $same_blocks = PageBlock::where('block_id', '=', $block->id)->get();
                foreach ($same_blocks as $same_block) {
                    $block_page_ids = @unserialize($same_block->content);
                    if (!empty($block_page_ids)) {
                        foreach ($block_page_ids as $k => $block_page_id) {
                            $block_page_id = Path::unParsePageId($block_page_id);
                            if ($currentPageId == $block_page_id) {
                                $page_ids[] = $same_block->page_id;
                                break;
                            }
                        }
                    }
                }
            }
        } elseif (!empty($block_data)) {
            $page_ids = unserialize($block_data);
        }
        if (!empty($page_ids)) {
            foreach ($page_ids as $page_id) {
                $parsedPageId = Path::unParsePageId($page_id, false);
                $pages[$page_id] = new PageDetails($parsedPageId[0], !empty($parsedPageId[1]) ? $parsedPageId[1] : 0);
            }
        }
        $template = !empty($options['view']) ? $options['view'] : $block->name;
        $selectPageViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.selectpages.';
        if (View::exists($selectPageViews . $template)) {
            return View::make($selectPageViews . $template, array('pages' => $pages))->render();
        } else {
            return 'Select pages template not found';
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $list_options = array();
        $parent = BlockSelectOption::where('block_id', '=', $block->id)->where('option', '=', 'parent')->first();
        if (!empty($parent)) {
            $list_options['parent'] = $parent->value;
        }
        $field_data = new \stdClass;
        $field_data->options = Page::get_page_list($list_options);
        $field_data->selected = unserialize($block_data);
        self::$edit_id = array($block->id);
        return $field_data;
    }

    public static function save($block_content)
    {
        if (!empty($block_content)) {
            return serialize($block_content);
        } else {
            return '';
        }
    }

    public static function filter($block_id, $search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($block_id);
        $page_ids = array();
        if (!empty($live_blocks)) {
            foreach ($live_blocks as $live_block) {
                $items = !empty($live_block->content) ? unserialize($live_block->content) : array();
                switch ($type) {
                    case '=':
                        if (in_array($search, $items)) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        foreach ($items as $item) {
                            if (strpos($item, $search) !== false) {
                                $page_ids[] = $live_block->page_id;
                            }
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

}