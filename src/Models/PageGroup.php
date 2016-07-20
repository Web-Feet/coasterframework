<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\BlockManager;
use Eloquent;

class PageGroup extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'page_group';

    /**
     * @var array
     */
    protected static $groupPages = [];

    /**
     * @var array
     */
    protected static $groupPagesFiltered = [];

    /**
     * Get all pageIds for this group, can filter by only live pages
     * @param bool $checkLive
     * @param bool $sort
     * @return array
     */
    public function itemPageIds($checkLive = false, $sort = false)
    {
        $filterType = $checkLive ? 'all' : 'live';
        $sortedSuffix = '-sorted';
        $sorted = $sort ? $sortedSuffix : '';

        if (empty(self::$groupPages[$this->id])) {
            self::$groupPages[$this->id] = [
                'all' => [],
                'live' => [],
            ];
            foreach (self::$groupPages[$this->id] as $filter => $arr) {
                self::$groupPages[$this->id][$filter.$sortedSuffix] = $arr;
            }
            $pages = Page::where('in_group', '=', $this->id)->get();
            if (!$pages->isEmpty()) {
                foreach ($pages as $page) {
                    self::$groupPages[$this->id]['all'][] = $page->id;
                    if ($page->is_live()) {
                        self::$groupPages[$this->id]['live'][] = $page->id;
                    }
                }
            }
        }

        if ($sort && empty(self::$groupPages[$this->id][$filterType.$sorted])) {
            if (!empty(self::$groupPages[$this->id][$filterType])) {
                if ($sortByAttribute = PageGroupAttribute::find($this->order_by_attribute_id)) {
                    // sort via live version attributes
                    $sortedPages = BlockManager::get_data_for_version(
                        new PageBlock,
                        -1,
                        array('block_id', 'page_id'),
                        array($sortByAttribute->item_block_id, self::$groupPages[$this->id][$filterType]),
                        'content ' . $this->order_dir
                    );
                    $sortedPageIds = [];
                    foreach ($sortedPages as $sortedPage) {
                        $sortedPageIds[] = $sortedPage->page_id;
                    }
                    // if sort by block is empty for any page in the group, then add at top
                    foreach (self::$groupPages[$this->id][$filterType] as $page) {
                        if (!in_array($page->id, $sortedPageIds)) {
                            array_unshift($sortedPageIds, $page->id);
                        }
                    }
                    self::$groupPages[$this->id][$filterType.$sorted] = $sortedPageIds;
                } else {
                    // if no sort by attribute
                    self::$groupPages[$this->id][$filterType.$sorted] = self::$groupPages[$this->id][$filterType];
                }
            } else {
                // if nothing to sort
                self::$groupPages[$this->id][$filterType.$sorted] = [];
            }
        }

        return self::$groupPages[$this->id][$filterType.$sorted];
    }

    /**
     * Filter by container block content
     * @param bool $checkLive
     * @param bool $sort
     * @return array
     */
    public function itemPageIdsFiltered($checkLive = false, $sort = false)
    {

        if (empty(self::$groupPagesFiltered[$this->id])) {

            $unfiltered = $this->itemPageIds($checkLive, $sort);

        }

        return [];
    }

}