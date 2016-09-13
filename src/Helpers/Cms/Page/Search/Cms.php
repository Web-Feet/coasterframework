<?php namespace CoasterCms\Helpers\Cms\Page\Search;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageSearchData;

class Cms
{
    /**
     * @var bool
     */
    protected $_onlyLive;

    /**
     * @var Page[]|\stdClass[]
     */
    protected $_pages;

    /**
     * @var array
     */
    protected $_weights;

    /**
     * Cms Page Search constructor.
     * @param bool $onlyLive
     */
    public function __construct($onlyLive = true)
    {
        $this->_onlyLive = $onlyLive;
        $this->_pages = [];
        $this->_weights = [];
    }

    /**
     * @param string $keyword
     * @param int $keywordAdditionalWeight
     */
    public function run($keyword, $keywordAdditionalWeight = 0)
    {
        $searchData = PageSearchData::with('block')->where('search_text', 'LIKE', '%' . $keyword . '%')->get();
        foreach ($searchData as $searchRow) {
            $page = Page::preload($searchRow->page_id);
            if (!$this->_onlyLive || $page->is_live()) {
                $this->_addWeight($page, (($b = $searchRow->block) ? $b->search_weight : 2) + $keywordAdditionalWeight);
            }
        }
    }

    /**
     * @param \stdClass|Page $page
     * @param int $weight
     */
    protected function _addWeight($page, $weight)
    {
        if (!isset($this->_weights[$page->id])) {
            $this->_weights[$page->id] = 0;
            $this->_pages[$page->id] = $page;
        }
        $this->_weights[$page->id] += $weight;
    }

    /**
     * @return array
     */
    public function getWeights()
    {
        return $this->_weights;
    }

    /**
     * @return Page[]|\stdClass[]
     */
    public function getPages()
    {
        return $this->_pages;
    }

}