<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;

class PageLoaderSpoof extends PageLoader
{

    /**
     * @var Page[]
     */
    protected $_spoofPageLevels;

    /**
     * @var bool
     */
    protected $_homePageTopLevel;

    /**
     * PageLoaderSpoof constructor.
     * If pageLevel is an array the last element will be the current page, with the previous ones being the page parents
     * Will add homepage as root parent by default
     *
     * @param Page|Page[] $spoofPageLevels
     * @param bool $homePageTopLevel
     */
    public function __construct($spoofPageLevels = [], $homePageTopLevel = true)
    {
        $this->_homePageTopLevel = $homePageTopLevel;
        $this->_spoofPageLevels = is_array($spoofPageLevels) ? $spoofPageLevels : [$spoofPageLevels];
        parent::__construct();
    }

    /**
     *
     */
    protected function _loadPageLevels()
    {
        $this->pageLevels = $this->_spoofPageLevels;
        if ($this->_homePageTopLevel && $homePage = self::_loadHomePage()) {
            array_unshift($this->pageLevels, $homePage);
        }
        $this->is404 = count($this->_spoofPageLevels) == 0;
    }

}