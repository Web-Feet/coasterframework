<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\BlockManager;
use Auth;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;

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
    public function groupAttributes()
    {
        return $this->hasMany('CoasterCms\Models\PageGroupAttribute', 'group_id')->orderBy('item_block_order_priority', 'desc');
    }

    /**
     * @return mixed
     */
    public function blockFilters()
    {
        return $this->groupAttributes()->where('filter_by_block_id', '>', 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pages()
    {
        return $this->belongsToMany('CoasterCms\Models\Page', 'page_group_pages', 'group_id', 'page_id');
    }

    /**
     * @return bool
     */
    public function canAddItems()
    {
        $containers = $this->containerPages();
        $canAddItems = false;
        foreach ($containers as $container) {
            if ($canAddItems = Auth::action('pages.add', ['page_id' => $container->id])) {
                break;
            }
        }
        return $canAddItems;
    }

    /**
     * @return bool
     */
    public function canAddContainers()
    {
        $containers = $this->containerPages();
        $canAddContainers = $containers->isEmpty();
        foreach ($containers as $container) {
            if ($canAddContainers = Auth::action('pages.edit', ['page_id' => $container->id])) {
                break;
            }
        }
        return $canAddContainers;
    }

    /**
     * @return array
     */
    public function containerPageIds()
    {
        $containerIds = [];
        $containers = $this->containerPages();
        foreach ($containers as $container) {
            $containerIds[] = $container->id;
        }
        return $containerIds;
    }

    /**
     * @param int $pageId
     * @return array
     */
    public function containerPageIdsFiltered($pageId)
    {
        $containerPageIds = [];
        foreach($this->containerPageIds() as $containerPageId) {
            if (in_array($pageId, $this->itemPageIdsFiltered($containerPageId))) {
                $containerPageIds[] = $containerPageId;
            }
        }
        return $containerPageIds;
    }

    /**
     * @return Collection
     */
    public function containerPages()
    {
        return Page::where('group_container', '=', $this->id)->get();
    }

    /**
     * @param int $pageId
     * @return Collection
     */
    public function containerPagesFiltered($pageId)
    {
        $pageIds = $this->containerPageIdsFiltered($pageId);
        return $pageIds ? Page::whereIn('id', $pageIds)->get() : new Collection;
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
                if ($sortByBlockIds = $this->orderAttributeBlocksIds()) {

                    $orderPriority = 1;
                    $sortedPageIds = [];
                    foreach (self::$groupPages[$this->id][$filterType] as $pageId) {
                        $sortedPageIds[$pageId] = [];
                    }

                    foreach ($sortByBlockIds as $sortByBlockId => $orderDir) {
                        $sortedPages = BlockManager::get_data_for_version(
                            new PageBlock,
                            -1,
                            array('block_id', 'page_id'),
                            array($sortByBlockId, self::$groupPages[$this->id][$filterType]),
                            'content ' . $orderDir
                        );
                        $sortOrder = 0;
                        foreach ($sortedPages as $index => $sortedPage) {
                            if (empty($sortedPages[$index-1]) || $sortedPages[$index-1]->content != $sortedPage->content) {
                                $sortOrder++;
                            }
                            $sortedPageIds[$sortedPage->page_id][$orderPriority] = $sortOrder;
                        }
                        // if sort by block is empty for any page in the group, then add at top
                        foreach ($sortedPageIds as $pageId => $orderValues) {
                            if (empty($sortedPageIds[$pageId][$orderPriority])) {
                                $sortedPageIds[$pageId][$orderPriority] = 0;
                            }
                        }
                        $orderPriority++;
                    }
                    uasort($sortedPageIds, function($a, $b) {
                        foreach ($a as $orderPriority => $orderValue) {
                            if ($orderValue < $b[$orderPriority]) {
                                return -1;
                            } elseif ($orderValue > $b[$orderPriority]) {
                                return 1;
                            }
                        }
                        return 0;
                    });

                    self::$groupPages[$this->id][$filterType.$sorted] = array_keys($sortedPageIds);

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

    /**
     * @param int $pageId
     * @param bool $checkLive
     * @param bool $sort
     * @return Collection
     */
    public function itemPageFiltered($pageId, $checkLive = false, $sort = false)
    {
        $pages = new Collection;
        if ($pageIds = $this->itemPageIdsFiltered($pageId, $checkLive, $sort)) {
            foreach ($this->pages as $page) {
                if (in_array($page->id, $pageIds)) {
                    $pages->add($page);
                }
            }
        }
        return $pages;
    }

    public function orderAttributeBlocksIds() {
        $blockIds = [];
        foreach ($this->groupAttributes as $attribute) {
            if ($attribute->item_block_order_priority) {
                $blockIds[$attribute->item_block_id] = $attribute->item_block_order_dir == 'desc' ? 'desc' : 'asc';
            }
        }
        return $blockIds;
    }

}