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
     * @return mixed
     */
    public function blockFilters()
    {
        return $this->hasMany('CoasterCms\Models\PageGroupAttribute', 'group_id')->where('filter_by_block_id', '>', 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pages()
    {
        return $this->belongsToMany('CoasterCms\Models\Page', 'page_group_pages', 'group_id', 'page_id');
    }

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
            $groupPages = $this->pages;
            if (!$groupPages->isEmpty()) {
                foreach ($groupPages as $groupPage) {
                    /** @var Page $groupPage */
                    self::$groupPages[$this->id]['all'][] = $groupPage->id;
                    if ($groupPage->is_live()) {
                        self::$groupPages[$this->id]['live'][] = $groupPage->id;
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
                    foreach (self::$groupPages[$this->id][$filterType] as $pageId) {
                        if (!in_array($pageId, $sortedPageIds)) {
                            array_unshift($sortedPageIds, $pageId);
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
     * Filter by container block content (filtered by group container content - pageId)
     * @param int $pageId
     * @param bool $checkLive
     * @param bool $sort
     * @return array
     */
    public function itemPageIdsFiltered($pageId, $checkLive = false, $sort = false)
    {
        $pageIds = $this->itemPageIds($checkLive, $sort);

        if (!empty($pageIds)) {

            $pageLang = PageLang::preload($pageId);

            foreach ($this->blockFilters as $blockFilter) {

                // get data to filter by
                $filterByBlock = Block::preload($blockFilter->filter_by_block_id);
                $filterBy = PageBlock::preload_block($pageId, $blockFilter->filter_by_block_id, $pageLang->live_version);
                $filterByContent = !empty($filterBy[Language::current()]) ? $filterBy[Language::current()]->content : null;
                if ($filterByBlock->type == 'selectmultiple') {
                    $filterByContentArr = unserialize($filterByContent);
                    $filterByContentArr = is_array($filterByContentArr) ? $filterByContentArr : [];
                } else {
                    $filterByContentArr = [$filterByContent];
                }
                $filterByContentArr = array_filter($filterByContentArr, function($filterByContentEl) { return !is_null($filterByContentEl); });

                if (!empty($filterByContentArr)) {
                    // get block data for block to filter on
                    $itemBlock = Block::preload($blockFilter->item_block_id);
                    $blockType = $itemBlock->get_class();

                    // run filter with filterBy content
                    $blockContentOnPageIds = [];
                    foreach ($filterByContentArr as $filterByContentEl) {
                        $newPageIds = $blockType::filter($itemBlock->id, $filterByContentEl, '=');
                        $blockContentOnPageIds = array_unique(array_merge($blockContentOnPageIds, $newPageIds));
                    }
                    $pageIds = array_intersect($pageIds, $blockContentOnPageIds);
                }

            }
            
        }

        return $pageIds;
    }

}