<?php namespace CoasterCms\Libraries\Builder\PageBuilder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Helpers\Cms\Theme\BlockUpdater;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Libraries\Builder\MenuBuilder;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Menu;
use CoasterCms\Models\Page;
use View;

class ThemeBuilderInstance extends PageBuilderInstance
{
    /**
     * @var string
     */
    protected $repeaterView;

    /**
     * @var string
     */
    protected $categoryView;

    /**
     * @var array
     */
    protected $repeaterTemplates;

    /**
     * @var BlockCategory[]
     */
    protected $blockCategoryIds;

    /**
     * @var bool
     */
    protected $loadOverrideFile;

    /**
     * @var array
     */
    public $repeaterBlocks;

    /**
     * @var array
     */
    public $selectBlocks;

    /**
     * @var array
     */
    public $formRules;

    /**
     * @var array
     */
    public $blockSettings;

    /**
     * @var array
     */
    public $templateBlocks;

    /**
     * @var array
     */
    public $coreTemplates;

    /**
     * @var array
     */
    public $errors;

    /**
     * ThemeBuilderInstance constructor.
     * @param PageLoader $pageLoader
     * @param bool $loadOverrideFile
     */
    public function __construct(PageLoader $pageLoader, $loadOverrideFile = false)
    {
        parent::__construct($pageLoader);
        $this->loadOverrideFile = $loadOverrideFile;
        $this->categoryView = '';
        $this->repeaterView = '';
        $this->errors = [];

        // set page override so check can be made if the actual page id is ever called
        $this->pageOverride = clone $this->page;
        $this->page->id = -666;

        $this->templateBlocks = [];
        $this->coreTemplates = ['__core_category', '__core_otherPage', '__core_repeater'];
        foreach ($this->coreTemplates as $coreTemplate) {
            $this->templateBlocks[$coreTemplate] = [];
        }

        if ($this->loadOverrideFile) {
            $this->_loadBlockOverwriteFile();
        }

        $this->_checkRepeaterTemplates();
        $this->_checkSelectBlocks();
        $this->_checkFormRules();
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
     * @param string $block_name
     * @param array $options
     * @return mixed|string
     */
    public function block($block_name, $options = [])
    {
        if ($this->_importIgnore($options)) {
            return $this->_returnValue('', $options);
        }

        if(empty($options['version'])) {
            $options['version'] = 0;
        }

        $block_name = strtolower($block_name);

        // get block type
        $block = Block::preloadClone($block_name);
        $block->name = $block_name;
        if (isset($this->blockSettings[$block_name]['type'])) {
            $block->type = $this->blockSettings[$block_name]['type'];
        }
        if (!$block->type) {
            $block->type = BlockUpdater::typeGuess($block_name);
        }

        // check if repeater view
        if (!empty($options['view'])) {
            $repeaterView = $options['view'];
        } else {
            $repeaterView = $block_name;
        }

        if ($block->type == 'repeater' || in_array($repeaterView, $this->repeaterTemplates)) {
            $tmp = $this->repeaterView;
            $this->repeaterView = $block_name;
            $output = $block->getTypeObject()->displayDummy($options);
            $this->repeaterView = $tmp;
        } else {
            // always use blank data for processing blocks
            $output = $block->getTypeObject()->displayDummy($options);
        }

        // if in a normal template (only if no page_id set or using the true page_id)
        if (!array_key_exists('page_id', $options) || $options['page_id'] === -666) {
            if ($this->repeaterView) {
                // if in a repeater template
                if (!isset($this->repeaterBlocks[$this->repeaterView])) {
                    $this->repeaterBlocks[$this->repeaterView] = [];
                }
                if (!in_array($block_name, $this->repeaterBlocks[$this->repeaterView])) {
                    $this->repeaterBlocks[$this->repeaterView][] = $block_name;
                }
                $template = '__core_repeater';
            } elseif ($this->categoryView) {
                $template = '__core_category';
            } else {
                $template = $this->template;
            }
        } else {
            $template = '__core_otherPage';
        }

        if (!isset($this->templateBlocks[$template])) {
            $this->templateBlocks[$template] = [];
        }
        if (!in_array($block_name, $this->templateBlocks[$template])) {
            $this->templateBlocks[$template][] = $block_name;
        }

        if (!empty($options['importNote'])) {
            if (!isset($this->blockSettings[$block_name])) {
                $this->blockSettings[$block_name] = [];
            }
            if (!isset($this->blockSettings[$block_name]['note'])) {
                $this->blockSettings[$block_name]['note'] = $options['importNote'];
            }
        }

        return $this->_returnValue($output, $options);
    }

    /**
     * @param $menu_name
     * @param array $options
     * @return string
     */
    public function menu($menu_name, $options = [])
    {
        if (!isset($this->loadMenus)) {
            $menus = Menu::all();
            $this->loadMenus = [];
            foreach ($menus as $menu) {
                $this->loadMenus[$menu->name] = $menu;
            }
        }
        if (empty($this->loadMenus[$menu_name])) {
            $menuView = 'themes.' . $this->theme . '.menus.' . (!empty($options['view'])?$options['view']:'default');
            $subLevel = 1;
            while (View::exists($menuView.'.submenu_'.$subLevel)) {
                $subLevel++;
            }
            $subLevel--;
            $newMenu = new Menu;
            $newMenu->label = ucwords(str_replace('_', ' ', $menu_name));
            $newMenu->name = $menu_name;
            $newMenu->max_sublevel = $subLevel;
            $newMenu->save();
            $this->loadMenus[$newMenu->name] = $newMenu;
        }
        $options['menu'] = $this->loadMenus[$menu_name];
        return MenuBuilder::customMenu([], 0, 1, 0, $options);
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
     * @param $options
     * @return string
     */
    protected function _renderCategoryWithoutPageData($options)
    {
        if ($this->_importIgnore($options)) {
            return '';
        }

        $view = !empty($options['view'])?$options['view']:'default';

        $catView = 'currentCategory=';

        if (!isset($this->categoryView) || $this->categoryView != $catView . $view) {

            $tmp = $this->categoryView;
            $this->categoryView = $catView . $view;

            $list = View::make(
                'themes.' . $this->theme . '.categories.' . $view . '.page',
                ['page' => new PageDetails(1), 'category_id' => 1, 'is_first' => true, 'is_last' => true, 'count' => 1, 'total' => 1]
            )->render();

            $output = View::make('themes.' . $this->theme . '.categories.' . $view . '.pages_wrap',
                ['pages' => $list, 'pagination' => '', 'links' => '', 'content' => '', 'category_id' => 1, 'total' => 1, 'html_content' => '', 'search_query' => '']
            )->render();

            $this->categoryView = $tmp;

        } else {
            $output = '';
        }

        return $output;
    }

    /**
     *
     */
    protected function _checkRepeaterTemplates()
    {
        $this->repeaterView = '';
        $this->repeaterTemplates = [];
        $this->repeaterBlocks = [];

        $repeaterPath = base_path('resources/views/themes/' . $this->theme . '/blocks/repeaters');
        if (is_dir($repeaterPath)) {
            foreach (scandir($repeaterPath) as $repeaterFile) {
                if ($repeaterTemplate = explode('.', $repeaterFile)[0]) {
                    $this->repeaterTemplates[] = $repeaterTemplate;
                }
            }
        }
    }

    /**
     *
     */
    protected function _checkSelectBlocks()
    {
        $this->selectBlocks = [];

        $selectOptions = base_path('resources/views/themes/' . $this->theme . '/import/blocks/select_options.csv');
        if (file_exists($selectOptions) && ($fileHandle = fopen($selectOptions, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($fileHandle)) !== false) {
                if ($row++ == 0 && $data[0] == 'Block Name') {
                    continue;
                }
                if (!isset($this->selectBlocks[$data[0]])) {
                    $this->selectBlocks[$data[0]] = [];
                }
                $this->selectBlocks[$data[0]][$data[2]] = $data[1];
            }
            fclose($fileHandle);
        }
    }

    /**
     *
     */
    protected function _checkFormRules()
    {
        $this->formRules = [];

        $formRules = base_path('resources/views/themes/' . $this->theme . '/import/blocks/form_rules.csv');
        if (file_exists($formRules) && ($fileHandle = fopen($formRules, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($fileHandle)) !== false) {
                if ($row++ == 0 && $data[0] == 'Form Template') {
                    continue;
                }
                if (!isset($this->formRules[$data[0]])) {
                    $this->formRules[$data[0]] = [];
                }
                $this->formRules[$data[0]][$data[1]] = $data[2];
            }
            fclose($fileHandle);
        }
    }

    /**
     *
     */
    protected function _loadBlockOverwriteFile()
    {
        $this->blockSettings = [];

        $selectOptions = base_path('resources/views/themes/' . $this->theme . '/import/blocks.csv');
        if (file_exists($selectOptions) && ($fileHandle = fopen($selectOptions, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($fileHandle)) !== false) {
                if ($row++ == 0 && $data[0] == 'Block Name') continue;
                if (!empty($data[0])) {
                    $fields = ['name', 'label', 'note', 'category_id', 'type', 'global_site', 'global_pages', 'templates', 'order'];
                    foreach ($fields as $fieldId => $field) {
                        if (isset($data[$fieldId])) {
                            $setting = trim($data[$fieldId]);
                            if ($setting != '') {
                                if (in_array($field, ['global_site', 'global_pages'])) {
                                    if (empty($setting) || strtolower($setting) == 'false' || strtolower($setting) == 'no' || strtolower($setting) == 'n') {
                                        $setting = false;
                                    } else {
                                        $setting = true;
                                    }
                                }
                                if ($field == 'category_id') {
                                    $setting = $this->_getBlockCategoryIdFromName($setting);
                                }
                                if ($field == 'name') {
                                    $setting = strtolower($setting);
                                }
                                $this->blockSettings[$data[0]][$field] = $setting;
                            }
                        }
                    }
                }
            }
            fclose($fileHandle);
        }
    }

    /**
     * @param $categoryName
     * @return mixed
     */
    protected function _getBlockCategoryIdFromName($categoryName)
    {
        if (!isset($this->blockCategoryIds)) {

            foreach (BlockCategory::all() as $category) {
                $this->blockCategoryIds[trim(strtolower($category->name))] = $category;
            }

            $categoryCsv = base_path('resources/views/themes/' . $this->theme . '/import/blocks/categories.csv');
            if (file_exists($categoryCsv) && ($fileHandle = fopen($categoryCsv, 'r')) !== false) {
                $row = 0;
                while (($data = fgetcsv($fileHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Block Category') continue;
                    if (!empty($data[0])) {
                        list($name, $order) = $data;
                        if (empty($this->blockCategoryIds[trim(strtolower($name))])) {
                            $newBlockCategory = new BlockCategory;
                            $newBlockCategory->name = trim($name);
                            $newBlockCategory->order = $order;
                            $newBlockCategory->save();
                            $this->blockCategoryIds[trim(strtolower($name))] = $newBlockCategory;
                        } else {
                            $this->blockCategoryIds[trim(strtolower($name))]->order = $order;
                            $this->blockCategoryIds[trim(strtolower($name))]->save();
                        }
                    }
                }
                fclose($fileHandle);
            }
        }

        if (empty($this->blockCategoryIds[trim(strtolower($categoryName))])) {
            $newBlockCategory = new BlockCategory;
            $newBlockCategory->name = trim($categoryName);
            $newBlockCategory->order = 0;
            $newBlockCategory->save();
            $this->blockCategoryIds[trim(strtolower($categoryName))] = $newBlockCategory;
        }

        return $this->blockCategoryIds[trim(strtolower($categoryName))]->id;
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
                if (!isset($this->blockSettings[$blockName])) {
                    $this->blockSettings[$blockName] = [];
                }
                if (!isset($this->blockSettings[$blockName]['type'])) {
                    $this->blockSettings[$blockName]['type'] = $blockType;
                }
            }
        }
        try {
            return parent::__call($name, $arguments);
        } catch (PageBuilderException $e) {
            $error = $e->getMessage() . ' (themes.' . $this->theme . '.templates.' . $this->template;
            $error .= $this->categoryView ? ' ' . $this->categoryView : $this->categoryView;
            $error .= $this->repeaterView ? ' ' . $this->repeaterView : $this->repeaterView;
            $error .= ')';
            $this->errors[] = $error;
            throw new PageBuilderException($error);
        }
    }

}
