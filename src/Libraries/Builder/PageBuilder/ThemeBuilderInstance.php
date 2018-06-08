<?php namespace CoasterCms\Libraries\Builder\PageBuilder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Helpers\Admin\Import\BlocksCollection;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Libraries\Builder\MenuBuilder;
use CoasterCms\Libraries\Builder\PageBuilderLogger;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\Menu;
use View;

class ThemeBuilderInstance extends DefaultInstance
{

    /**
     * @var bool
     */
    public $isThemeBuilder;

    /**
     * @var BlocksCollection
     */
    protected $_blocksCollection;

    /**
     * @var array
     */
    protected $_renderPath;

    /**
     * @var array
     */
    protected $_renderBlockOrder;

    /**
     * @var array
     */
    protected $_repeaterTemplates;

    /**
     * @var array
     */
    protected $_blocksWithSelectOptions;

    /**
     * @var Menu[]
     */
    protected $_existingMenus;

    /**
     * @ int
     */
    const DUMMY_ORIGINAL_PAGE_ID = -10;

    /**
     * ThemeBuilderInstance constructor.
     * @param PageBuilderLogger $logger
     * @param PageLoader $pageLoader
     * @param BlocksCollection $blocksCollection
     */
    public function __construct(PageBuilderLogger $logger, PageLoader $pageLoader, $blocksCollection)
    {
        parent::__construct($logger, $pageLoader);
        $this->errors = [];
        $this->isThemeBuilder = true; // can use $pb->getData('isThemeBuilder') in templates now

        // set page override so check can be made if the actual page id is ever called
        $this->page->id = static::DUMMY_ORIGINAL_PAGE_ID;

        $this->_blocksCollection = $blocksCollection;
        $this->_blocksCollection->setScope('file');

        $this->_renderPath = [];
        $this->_renderBlockOrder = [];

        $this->_checkRepeaterTemplates();
        $this->_checkBlocksWithSelectOptions();
        $this->_loadExistingMenus();
    }

    /**
     * @param array $renderPath
     */
    public function setRenderPath($renderPath)
    {
        $this->_renderPath = $renderPath;
    }

    /**
     *
     */
    protected function _checkRepeaterTemplates()
    {
        $this->_repeaterTemplates = [];
        $repeaterPaths = [base_path('resources/views/themes/' . $this->theme . '/blocks/repeater')];
        $repeaterPaths[] = $repeaterPaths[0].'s';
        foreach ($repeaterPaths as $repeaterPath) {
            if (is_dir($repeaterPath)) {
                foreach (scandir($repeaterPath) as $repeaterFile) {
                    if ($repeaterTemplate = explode('.', $repeaterFile)[0]) {
                        $this->_repeaterTemplates[] = $repeaterTemplate;
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function _checkBlocksWithSelectOptions()
    {
        $selectImport = new SelectOptionImport();
        $this->_blocksWithSelectOptions = $selectImport->setTheme($this->theme)->getSelectBlockNames();
    }

    /**
     *
     */
    protected function _loadExistingMenus()
    {
        $menus = Menu::all();
        $this->_existingMenus = [];
        foreach ($menus as $menu) {
            $this->_existingMenus[$menu->name] = $menu;
        }
    }

    /**
     * @param array $options
     * @return string
     */
    public function sitemap($options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * @param int $categoryPageId
     * @param array|null $pages
     * @param array $options
     * @return string
     */
    public function pages($categoryPageId = null, $pages = null, $options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function category($options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function filter($blockName, $search, $options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * @param string $blockName
     * @param string $search
     * @param array $options
     * @return string
     */
    public function categoryFilter($blockName, $search, $options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function search($options = [])
    {
        return $this->_renderCategoryWithoutPageData($options);
    }

    /**
     * Return data for block as json
     * @param string $blockName
     * @param array $options
     * @return string json
     */
    public function blockJson($blockName, $options = [])
    {
        if (array_key_exists('returnAll', $options) && $options['returnAll']) {
            // don't load blocks from db (otherwise every db block will always be found)
            return collect([])->toJson();
        }
        return parent::blockJson($blockName, $options);
    }

    /**
     * @param string $blockName
     * @param array $options
     * @param string $fn
     * @return mixed|string
     */
    protected function _block($blockName, $options = [], $fn = 'display')
    {
        if ($this->_importIgnore($options)) {
            return $this->_returnValue('', $options);
        }

        // load block details
        $block = new Block();
        $dbBlock = Block::preload($blockName);
        if ($dbBlock->exists) { // if block exists in db then load data
            $checkDb = $this->_blocksCollection->getBlock($blockName, 'db');
            if (count($checkDb->blockData) == 1) { // if block data not loaded from db
                $checkDb->setBlockData($dbBlock->getAttributes())
                    ->setGlobalData(['show_in_global' => 0, 'show_in_pages' => 0]);
            }
        }
        $aggregatedBlock = $this->_blocksCollection->getAggregatedBlock($blockName);
        foreach ($aggregatedBlock->blockData as $field => $value) {
            $block->$field = $value;
        }

        // set version
        $options['version'] = empty($options['version']) ? 0 : $options['version'];

        // render block
        $this->_setType($block, $options);
        $this->_renderPath[] = ['block_' . $block->type => $block->name];
        // always use blank block content for processing blocks for consistency
        if ($fn == 'display') {
            // displayDummy does not require any block content
            $output = $block->getTypeObject()->displayDummy($options);
        } else {
            // use blank data for the other functions
            $output = $block->getTypeObject()->$fn('', $options);
        }
        array_pop($this->_renderPath);

        // add block to template array based on where it has been found
        $this->_addToTemplate($block, $options);
        $this->_order($block->name);

        // set block note
        if (!empty($options['importNote'])) {
            $this->_blocksCollection->getBlock($blockName)->setBlockData(['note' => $options['importNote']]);
        }

        return $this->_returnValue($output, $options);
    }

    /**
     * @param $menuName
     * @param array $options
     * @return string
     */
    public function menu($menuName, $options = [])
    {
        $this->_renderPath[] = ['menu' => $menuName];
        if (!array_key_exists($menuName, $this->_existingMenus)) {
            $menuView = 'themes.' . $this->theme . '.menus.' . (!empty($options['view'])?$options['view']:'default');
            $subLevel = 0;
            while (View::exists($menuView . '.submenu_' . ($subLevel+1))) {
                $subLevel++;
            }
            $newMenu = new Menu;
            $newMenu->label = ucwords(str_replace('_', ' ', $menuName));
            $newMenu->name = $menuName;
            $newMenu->max_sublevel = $subLevel;
            $newMenu->save();
            $this->_existingMenus[$menuName] = $newMenu;
        }
        $options['menu'] = $this->_existingMenus[$menuName];
        $renderedMenu = MenuBuilder::customMenu([], 0, 1, 0, $options);
        array_pop($this->_renderPath);
        return $renderedMenu;
    }

    /**
     * @param $options
     * @return string
     */
    protected function _renderCategoryWithoutPageData($options)
    {
        if ($this->_importIgnore($options)) {
            return $this->_returnValue('', $options);
        }

        $view = !empty($options['view']) ? $options['view'] : 'default';
        $loop = 0; // avoid getting stuck in loop
        foreach ($this->_renderPath as $renderItem) {
            if (array_key_exists('category', $renderItem) && $renderItem['category'] == $view) {
                $loop++;
            }
        }

        if ($loop < 5) {
            $this->_renderPath[] = ['category' => $view];
            $list = View::make(
                'themes.' . $this->theme . '.categories.' . $view . '.page',
                ['page' => new PageDetails(1), 'category_id' => 1, 'is_first' => true, 'is_last' => true, 'count' => 1, 'total' => 1]
            )->render();
            $output = View::make('themes.' . $this->theme . '.categories.' . $view . '.pages_wrap',
                ['pages' => $list, 'pagination' => '', 'links' => '', 'content' => '', 'category_id' => 1, 'total' => 1, 'html_content' => '', 'search_query' => '']
            )->render();
            array_pop($this->_renderPath);
        } else {
            $output = '';
        }

        return $this->_returnValue($output, $options);
    }

    /**
     * @param Block $block
     * @param array $options
     */
    protected function _setType(&$block, $options)
    {
        if (!$block->type) {
            $view = empty($options['view']) ? $block->name : $options['view'];
            if (in_array($view, $this->_repeaterTemplates)) {
                $block->type = 'repeater';
            } else {
                $typesArr = [
                    'video' => ['vid'],
                    'text' => ['text', 'desc', 'keywords', 'intro', 'address', 'html', 'lead'],
                    'richtext' => ['richtext', 'content'],
                    'image' => ['image', 'img', 'banner', 'logo'],
                    'link' => ['link', 'url'],
                    'datetime' => ['date', 'datetime'],
                    'string' => ['link_text', 'caption', 'title'],
                    'form' => ['form', 'contact'],
                    'select' => ['select'],
                    'selectmultiple' => ['selectmultiple', 'multipleselect'],
                    'selectpage' => ['selectpage'],
                    'selectpages' => ['selectpages']
                ];
                $typeFound = 'string';
                foreach ($typesArr as $type => $matches) {
                    foreach ($matches as $match) {
                        if (stristr($block, $match)) {
                            $typeFound = $type;
                        }
                    }
                }
                if (strpos($typeFound, 'select') === false && array_key_exists($block->name, $this->_blocksWithSelectOptions)) {
                    $typeFound = 'select';
                }
                $block->type = $typeFound;
            }
            $this->_blocksCollection->getBlock($block->name, 'guess')->setBlockData(['type' => $block->type]);
        }
    }

    /**
     * @param Block $block
     * @param array $options
     */
    protected function _addToTemplate($block, $options)
    {
        $customPageId = array_key_exists('page_id', $options) ? $options['page_id'] : $this->pageId();
        if ($customPageId === $this->pageId(true)) {
            if ($inRepeaterBlockName = $this->_inRepeaterView()) {
                // block in a repeater template
                $this->_blocksCollection->getBlock($block->name)->addRepeaterBlocks($inRepeaterBlockName);
                // block set as child block of repeater
                $this->_blocksCollection->getBlock($inRepeaterBlockName)->addRepeaterChildBlocks($block->name);
            } elseif ($categoryViews = $this->_inCategoryView()) {
                // block in dynamic category view (could be based on page id, search, etc.)
                $this->_blocksCollection->getBlock($block->name)->addCategoryTemplates($categoryViews);
            } elseif ($this->template) {
                // block definitely in template
                $this->_blocksCollection->getBlock($block->name)->addTemplates($this->template);
            }
        } else {
            // if a page has been specified and it's not the same as the original page id
            $customPageId = (is_null($customPageId) ? 'Content defined' : $customPageId);
            $this->_blocksCollection->getBlock($block->name)->addSpecifiedPageIds([$customPageId]);
        }
    }

    /**
     * @return array
     */
    protected function _inCategoryView()
    {
        $renderItems = [];
        foreach ($this->_renderPath as $renderItem) {
            if (array_key_exists('category', $renderItem)) {
                $renderItems[] = $renderItem['category'];
            }
        }
        return array_unique($renderItems);
    }

    /**
     * @return string
     */
    protected function _inRepeaterView()
    {
        $renderPath = array_reverse($this->_renderPath);
        foreach ($renderPath as $renderItem) {
            if (array_key_exists('block_repeater', $renderItem)) {
                return $renderItem['block_repeater'];
            }
        }
        return '';
    }

    /**
     * @param string $blockName
     */
    protected function _order($blockName)
    {
        if ($this->_renderPath) {
            $firstRenderItem = reset($this->_renderPath);
            $template = key($firstRenderItem) . '.' . current($firstRenderItem);
        } else {
            $template = 'none';
        }
        if (!array_key_exists($template, $this->_renderBlockOrder)) {
            $this->_renderBlockOrder[$template] = [];
        }
        if (!array_key_exists($blockName, $this->_renderBlockOrder[$template])) {
            $this->_renderBlockOrder[$template][$blockName] = null;
        }
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->_renderBlockOrder;
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function _importIgnore($options)
    {
        $ignoreSnakeCaseOpts = ['import_ignore', 'review_ignore'];

        $ignoreOpts = [];
        foreach ($ignoreSnakeCaseOpts as $ignoreSnakeCaseOpt) {
            $ignoreOpts[$ignoreSnakeCaseOpt] = null;
            $ignoreOpts[camel_case($ignoreSnakeCaseOpt)] = null;
        }

        foreach (array_intersect_key($options, $ignoreOpts) as $ignoreValue) {
            if ($ignoreValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $output
     * @param array $options
     * @return mixed
     */
    protected function _returnValue($output, $options)
    {
        $returnSnakeCaseOpts = ['import_return_value', 'review_return_value'];

        $returnOpts = [];
        foreach ($returnSnakeCaseOpts as $returnSnakeCaseOpt) {
            $returnOpts[$returnSnakeCaseOpt] = null;
            $returnOpts[camel_case($returnSnakeCaseOpt)] = null;
        }

        foreach (array_intersect_key($options, $returnOpts) as $returnValue) {
            return $returnValue;
        }
        return $output;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|string
     * @throws PageBuilderException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'block_') === 0) {
            $validTypes = Block::getBlockClasses();
            $blockType = strtolower(substr($name, 6));
            if (!empty($validTypes[$blockType])) {
                $blockName = $arguments[0];
                $this->_blocksCollection->getBlock($blockName, 'block_')->setBlockData(['type' => $blockType]);
            }
        }
        try {
            return parent::__call($name, $arguments);
        } catch (PageBuilderException $e) {
            $renderItems = [];
            foreach ($this->_renderPath as $renderItem) {
                $renderItems[] = implode('=>', $renderItem);
            }
            $appendRenderItems = $renderItems ? ' [' . implode(',', $renderItems) . ']' : '';
            $error = $e->getMessage() . ' (themes.' . $this->theme . '.templates.' . $this->template  . $appendRenderItems . ')';
            throw new PageBuilderException($error);
        }
    }

}
