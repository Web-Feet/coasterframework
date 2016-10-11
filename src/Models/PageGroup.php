<?php namespace CoasterCms\Models;

use Auth;
use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;

class PageGroup extends Eloquent
{
    use DataPreLoad;

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
    public function canEditItems()
    {
        $containers = $this->containerPages();
        $canEditItems = $containers->isEmpty();
        foreach ($containers as $container) {
            if ($canEditItems = Auth::action('pages.edit', ['page_id' => $container->id])) {
                break;
            }
        }
        return $canEditItems;
    }

    /**
     * @return bool
     */
    public function canPublishItems()
    {
        $containers = $this->containerPages();
        $canEditItems = false;
        foreach ($containers as $container) {
            if ($canEditItems = Auth::action('pages.version-publish', ['page_id' => $container->id])) {
                break;
            }
        }
        return $canEditItems;
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
        $filterType = $checkLive ? 'live' : 'all';
        $sortedSuffix = '-sorted';
        $sorted = $sort ? $sortedSuffix : '';

        if (empty(self::$groupPages[$this->id])) {
            $groupPages = $this->pages;

            self::$groupPages[$this->id] = [
                'all' => [],
                'live' => [],
            ];

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

        if ($sort && !array_key_exists($filterType.$sorted, self::$groupPages[$this->id])) {
            if (!empty(self::$groupPages[$this->id][$filterType])) {
                if ($sortByBlockIds = $this->orderAttributeBlocksIds()) {

                    $orderPriority = 1;
                    $sortedPageIds = [];
                    foreach (self::$groupPages[$this->id][$filterType] as $pageId) {
                        $sortedPageIds[$pageId] = [];
                    }

                    foreach ($sortByBlockIds as $sortByBlockId => $orderDir) {
                        $sortedPages = Block::getDataForVersion(
                            new PageBlock,
                            -1,
                            ['block_id' => $sortByBlockId, 'page_id' => self::$groupPages[$this->id][$filterType]],
                            ['content' => $orderDir]
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
        $filterType = $checkLive ? 'all' : 'live';
        $sortedSuffix = '-sorted';
        $sorted = $sort ? $sortedSuffix : '';

        self::$groupPagesFiltered[$this->id] = empty(self::$groupPagesFiltered[$this->id]) ? [] : self::$groupPagesFiltered[$this->id];

        if (!array_key_exists($filterType.$sorted, self::$groupPagesFiltered[$this->id])) {

            if ($pageIds = $this->itemPageIds($checkLive, $sort)) {

                foreach ($this->blockFilters as $blockFilter) {

                    // get data to filter by
                    $filterByBlock = Block::preload($blockFilter->filter_by_block_id);
                    $filterBy = PageBlock::preload_block($pageId, $blockFilter->filter_by_block_id, -1);
                    $filterByContent = !empty($filterBy[Language::current()]) ? $filterBy[Language::current()]->content : null;
                    if ($filterByBlock->type == 'selectmultiple') {
                        $filterByContentArr = unserialize($filterByContent);
                        $filterByContentArr = is_array($filterByContentArr) ? $filterByContentArr : [];
                    } else {
                        $filterByContentArr = [$filterByContent];
                    }
                    $filterByContentArr = array_filter($filterByContentArr, function ($filterByContentEl) {
                        return !is_null($filterByContentEl);
                    });

                    if (!empty($filterByContentArr)) {
                        // get block data for block to filter on
                        $blockType = Block::preload($blockFilter->item_block_id)->getTypeObject();

                        // run filter with filterBy content
                        $filteredPageIds = [];
                        foreach ($pageIds as $groupPageId) {
                            foreach ($filterByContentArr as $filterByContentEl) {
                                $groupPageBlock = PageBlock::preload_block($groupPageId, $blockFilter->item_block_id, -1, 'page_id');
                                $groupPageBlockContent = !empty($groupPageBlock[Language::current()]) ? $groupPageBlock[Language::current()]->content : '';
                                if ($blockType->filter($groupPageBlockContent, $filterByContentEl, '=')) {
                                    $filteredPageIds[] = $groupPageId;
                                    break;
                                }
                            }
                        }

                        $pageIds = $filteredPageIds;
                    }

                }

            }

            self::$groupPagesFiltered[$this->id][$filterType.$sorted] = $pageIds;
        }

        return self::$groupPagesFiltered[$this->id][$filterType.$sorted];
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
        if ($groupPageIds = $this->itemPageIdsFiltered($pageId, $checkLive, $sort)) {
            foreach ($groupPageIds as $groupPageId) {
                $pages->add(Page::preload($groupPageId));
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