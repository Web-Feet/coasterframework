<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Core\BlockManager;
use Eloquent;

class PageGroup extends Eloquent
{
    protected $table = 'page_group';

    private static $group_pages = array();

    public static function page_ids($group_id, $check_live = false, $sort = false)
    {
        if (empty(self::$group_pages[$group_id][$check_live]) || (!empty(self::$group_pages[$group_id][$check_live]) && (!self::$group_pages[$group_id][$check_live]->sort && $sort))) {
            $pages = Page::where('in_group', '=', $group_id)->get();
            $page_ids = array();
            if (!empty($pages)) {
                foreach ($pages as $page) {
                    if (!$check_live || ($check_live && $page->is_live())) {
                        $page_ids[] = $page->id;
                    }
                }
                if (!empty($page_ids) && $sort) {
                    $group = self::find($group_id);
                    if ($group->order_by_attribute_id > 0) {
                        $sort_by_attribute = PageGroupAttribute::find($group->order_by_attribute_id);
                        // sort via live version attributes
                        $sorted_pages = BlockManager::get_data_for_version(
                            new PageBlock,
                            -1,
                            array('block_id', 'page_id'),
                            array($sort_by_attribute->item_block_id, $page_ids),
                            'content ' . $group->order_dir
                        );
                        $sorted_page_ids = array();
                        foreach ($sorted_pages as $sorted_page) {
                            $sorted_page_ids[] = $sorted_page->page_id;
                        }
                        // if sort by block is empty for any page in the group, then add at top
                        foreach ($pages as $page) {
                            if (!in_array($page->id, $sorted_page_ids)) {
                                array_unshift($sorted_page_ids, $page->id);
                            }
                        }
                        $page_ids = $sorted_page_ids;
                    }
                }
            }
            $save = new \stdClass;
            $save->sort = $sort;
            $save->page_ids = $page_ids;
            if (!isset(self::$group_pages[$group_id])) {
                self::$group_pages[$group_id] = array();
            }
            self::$group_pages[$group_id][$check_live] = $save;
        }
        return self::$group_pages[$group_id][$check_live]->page_ids;
    }

}