<?php namespace CoasterCms\Libraries\Builder\PageBuilder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Libraries\Builder\MenuBuilder;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\Menu;
use View;

class ThemeBuilderInstance extends PageBuilderInstance
{

    /**
     * @var array
     */
    public $errors;

    /**
     * @var array
     */
    public $originalBlockData;

    /**
     * @var array
     */
    public $blockData;

    /**
     * @var array
     */
    public $blockTemplates;

    /**
     * @var array
     */
    public $blockOtherViews;

    /**
     * @var array
     */
    protected $_renderPath;

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
     * @param PageLoader $pageLoader
     * @param array $blockData
     * @param array $blockTemplates
     */
    public function __construct(PageLoader $pageLoader, $blockData = [], $blockTemplates = [])
    {
        parent::__construct($pageLoader);
        $this->errors = [];

        // set page override so check can be made if the actual page id is ever called
        $this->pageOverride = clone $this->page;
        $this->page->id = static::DUMMY_ORIGINAL_PAGE_ID;

        $this->originalBlockData = $blockData;
        $this->blockData = $blockData;

        $this->blockTemplates = $blockTemplates;
        $this->blockOtherViews = [
            'repeater' => [],
            'category' => [],
            'otherPage' => []
        ];

        $this->_renderPath = [];

        $this->_checkRepeaterTemplates();
        $this->_checkBlocksWithSelectOptions();
        $this->_loadExistingMenus();
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

        if (!array_key_exists($blockName, $this->blockData)) {
            $this->blockData[$blockName] = [];
        }

        // load block details
        $block = Block::preload($blockName);
        $block->name = $blockName;
        if (array_key_exists($blockName, $this->blockData)) {
            foreach ($this->blockData[$blockName] as $field => $value) {
                $block->$field = $value;
            }
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

        // set block note
        if (!empty($options['importNote'])) {
            if (!array_key_exists('note', $this->blockData[$blockName])) {
                $this->blockData[$blockName]['note'] = $options['importNote'];
            }
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
            return '';
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

        return $output;
    }

    /**
     * @param Block $block
     * @param array $options
     */
    protected function _setType(&$block, $options)
    {
        if (!$block->type && !array_key_exists('type', $this->blockData[$block->name])) {
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
            $this->blockData[$block->name]['type'] = $block->type;
        }
    }

    /**
     * @param Block $block
     * @param array $options
     */
    protected function _addToTemplate($block, $options)
    {
        if (!array_key_exists('page_id', $options) || $options['page_id'] === static::DUMMY_ORIGINAL_PAGE_ID) {
            if ($inRepeaterBlockName = $this->_inRepeaterView()) {
                // block in a repeater template
                if (!array_key_exists($inRepeaterBlockName, $this->blockOtherViews['repeater'])) {
                    $this->blockOtherViews['repeater'][$inRepeaterBlockName] = [];
                }
                if (!in_array($block->name, $this->blockOtherViews['repeater'][$inRepeaterBlockName])) {
                    $this->blockOtherViews['repeater'][$inRepeaterBlockName][] = $block->name;
                }
            } elseif ($categoryViews = $this->_inCategoryView()) {
                // block in dynamic category view (could be based on page id, search, etc.)
                if (!array_key_exists($block->name, $this->blockOtherViews['category'])) {
                    $this->blockOtherViews['category'][$block->name] = [];
                }
                $this->blockOtherViews['category'][$block->name] = array_unique(array_merge($categoryViews, $this->blockOtherViews['category'][$block->name]));
            } else {
                // block definitely in template
                if (!array_key_exists($block->name, $this->blockTemplates)) {
                    $this->blockTemplates[$block->name] = [];
                }
                if (!in_array($this->template, $this->blockTemplates[$block->name])) {
                    $this->blockTemplates[$block->name][] = $this->template;
                }
            }
        } else {
            // if a page has been specified and it's not the same as the original page id
            $this->blockOtherViews['otherPage'][] = $block->name;
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
            if ($returnValue) {
                return $returnValue;
            }
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
                if (!isset($this->blockData[$blockName])) {
                    $this->blockData[$blockName] = [];
                }
                if (!array_key_exists('type', $this->originalBlockData[$blockName])) {
                    $this->blockData[$blockName]['type'] = $blockType;
                }
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
            $this->errors[] = $error;
            throw new PageBuilderException($error);
        }
    }

}