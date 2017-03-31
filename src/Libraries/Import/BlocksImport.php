<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Admin\Import\BlocksCollection;
use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\Template;
use CoasterCms\Models\TemplateBlock;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeBlock;
use View;

class BlocksImport extends AbstractImport
{

    /**
     * @var string
     */
    protected $_currentBlockName;

    /**
     * @var BlockCategory[]
     */
    protected $_blockCategoriesByName;

    /**
     * @var array
     */
    protected $_templateList;

    /**
     * @var array
     */
    protected $_blockData;

    /**
     * @var array
     */
    protected $_blockGlobals;

    /**
     * @var array
     */
    protected $_blockTemplates;

    /**
     * @var array
     */
    protected $_blockOtherViewData;

    /**
     * @var BlocksCollection
     */
    protected $_blocksCollection;

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => [
                'mapTo' => 'name',
                'mapFn' => '_toLowerTrim',
                'validate' => 'required'
            ],
            'Block Label' => [
                'mapTo' => 'label'
            ],
            'Block Note' => [
                'mapTo' => 'note'
            ],
            'Block Category' => [
                'mapTo' => 'category_id',
                'mapFn' => '_toCategoryId'
            ],
            'Block Type' => [
                'mapTo' => 'type'
            ],
            'Global (show in site-wide)' => [
                'mapFn' => '_mapGlobalSiteWide'
            ],
            'Global (show in pages)' => [
                'mapFn' => '_mapGlobalPages'
            ],
            'Templates' => [
                'mapFn' => '_mapTemplates'
            ],
            'Block Order' => [
                'mapTo' => 'order'
            ],
            'Block Active' => [
                'mapTo' => 'active'
            ]
        ];
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if (($this->_additionalData && !array_key_exists('theme', $this->_additionalData)) || !is_a($this->_additionalData['theme'], Theme::class)) {
            $this->_validationErrors[] = 'Theme must be specified before blocks can be imported';
        }
        return parent::validate();
    }

    /**
     *
     */
    protected function _loadTemplateList()
    {
        $this->_templateList = [];
        $themePath = base_path('resources/views/themes/' . $this->_additionalData['theme']->theme . '/templates');
        if (is_dir($themePath)) {
            foreach (scandir($themePath) as $templateFile) {
                $templateName = current(explode('.', $templateFile));
                if ($templateName && !is_dir($themePath . '/' . $templateFile)) {
                    $this->_templateList[] = $templateName;
                }
            }
        }
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $categoryImport = new \CoasterCms\Libraries\Import\Blocks\CategoryImport;
        $categoryImport->setTheme($this->_additionalData['theme'])->run();
        $this->_blockCategoriesByName = $categoryImport->getBlockCategoriesByName();
        $formRulesImport = new \CoasterCms\Libraries\Import\Blocks\FormRulesImport();
        $formRulesImport->setTheme($this->_additionalData['theme'])->run();
        $this->_loadTemplateList();
        $this->_blocksCollection = new BlocksCollection();
        $this->_blocksCollection->setScope('csv');
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentBlockName = $this->_toLowerTrim($this->_importCurrentRow['Block Name']);
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        if ($importFieldData !== '') {
            $this->_blocksCollection->getBlock($this->_currentBlockName)->setBlockData([$importInfo['mapTo'] => $importFieldData]);
        }
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toCategoryId($importFieldData)
    {
        $importFieldData = trim($importFieldData);
        if ($importFieldData !== '') {
            $importFieldDataLower = strtolower($importFieldData);
            if (!array_key_exists($importFieldDataLower, $this->_blockCategoriesByName)) {
                $newBlockCategory = new BlockCategory;
                $newBlockCategory->name = $importFieldData;
                $newBlockCategory->order = 0;
                $newBlockCategory->save();
                $this->_blockCategoriesByName[$importFieldDataLower] = $newBlockCategory;
            }
            return $this->_blockCategoriesByName[$importFieldDataLower]->id;
        }
        return '';
    }

    /**
     * @param string $importFieldData
     */
    protected function _mapTemplates($importFieldData)
    {
        if ($importFieldData !== '') {
            $templates = ($importFieldData == '*') ? $this->_templateList : explode(',', $importFieldData);
            $this->_blocksCollection->getBlock($this->_currentBlockName)->addTemplates($templates);
            $this->_templateList = array_unique(array_merge($this->_templateList, $templates));
        }
    }

    /**
     * @param string $importFieldData
     * @param string $globalSetting
     */
    protected function _mapGlobal($importFieldData, $globalSetting = '')
    {
        if ($importFieldData !== '') {
            $value = $this->_toBool($importFieldData) ? 1 : 0;
            $this->_blocksCollection->getBlock($this->_currentBlockName)->setGlobalData([$globalSetting => $value]);
        }
    }

    /**
     * @param string $importFieldData
     */
    protected function _mapGlobalPages($importFieldData)
    {
        $this->_mapGlobal($importFieldData, 'show_in_pages');
    }

    /**
     * @param string $importFieldData
     */
    protected function _mapGlobalSiteWide($importFieldData)
    {
        $this->_mapGlobal($importFieldData, 'show_in_global');
    }

    /**
     *
     */
    protected function _afterRun()
    {
        $this->_blocksCollection->setScope('file');


        PageBuilder::setClass(
            PageBuilder\ThemeBuilderInstance::class,
            [$this->_blocksCollection],
            PageLoaderDummy::class,
            [$this->_additionalData['theme']->theme]
        );
        $this->_renderThemeFiles();
        //$this->_renderCsvBlocks(); // do after theme render as there is less context
        dd($this->_blocksCollection->getAggregatedBlocks());


        // fill in and missing block data (from db if poss, failing that have a guess)
        $this->_loadMissingBlockDataFromDb();
        foreach ($this->_blockData as $blockName => &$details) {
            $details += [
                'name' => $blockName,
                'label' => ucwords(str_replace('_', ' ', $blockName)),
                'active' => 1,
                'search_weight' => 1,
                'note' => ''
            ];
        }
        // TODO set order guesses
        $this->_categoryIdGuess();
        $this->_loadMissingIsGlobalDataFromDb();
        $this->_isGlobalGuess();
    }

    /**
     * Get all extra block data from theme files
     */
    protected function _renderThemeFiles()
    {
        if ($this->_templateList) {
            foreach ($this->_templateList as $templateName) {
                $templateView = 'themes.' . $this->_additionalData['theme']->theme . '.templates.' . $templateName;
                if (View::exists($templateView)) {
                    PageBuilder::setData('template', $templateName);
                    View::make($templateView)->render();
                }
            }
            if ($errors = PageBuilder::getData('errors')) {
                echo 'Could not complete block import, errors found in theme:';
                dd($errors);
            }
        }
    }

    /**
     * Run all blocks from import csv as they may not have be found and rendered in the theme files
     */
    protected function _renderCsvBlocks()
    {
        PageBuilder::setData('template', '');
        foreach ($this->_blockData as $blockName => $blockData) {
            PageBuilder::block($blockName);
        }
    }

    /**
     *
     */
    protected function _loadMissingBlockDataFromDb()
    {
        foreach ($this->_blockData as $blockName => &$blockData) {
            $block = Block::preload($blockName);
            if ($block->exists) {
                foreach ($block->getAttributes() as $field => $value) {
                    if (!array_key_exists($field, $blockData) && !in_array($field, ['updated_at', 'created_at'])) {
                        $blockData[$field] = $block->$field;
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function _categoryIdGuess()
    {
        // load default categories and their ids
        $defaultCategoryIds = [];
        $defaultCategorySearchStrings = [
            'header' => ['head'],
            'main' => ['main', 'default'],
            'banner' => ['banner', 'carousel'],
            'footer' => ['foot'],
            'seo' => ['seo']
        ];
        if (!empty($this->_blockCategoriesByName)) {
            $defaultCategoryIds['main'] = reset($this->_blockCategoriesByName)->id;
        }
        foreach ($this->_blockCategoriesByName as $blockName => $blockCategory) {
            foreach ($defaultCategorySearchStrings as $defaultCategory => $searchStrings) {
                foreach ($searchStrings as $searchString) {
                    if (stristr($blockCategory->name, $searchString)) {
                        $defaultCategoryIds[$defaultCategory] = $blockCategory->id;
                    }
                }
            }
        }

        // if block is repeater and category_id is not set create own category
        $extraMatches = [];
        foreach ($this->_blockData as $blockName => $blockData) {
            if (!array_key_exists('category_id', $blockData) && $blockData['type'] == 'repeater') {
                $createNewCategory = true;
                foreach ($defaultCategorySearchStrings as $defaultCategory => $searchStrings) {
                    foreach ($searchStrings as $searchString) {
                        if (stristr($blockName, $searchString)) {
                            $createNewCategory = false;
                        }
                    }
                }
                if ($createNewCategory) {
                    $categoryName = str_plural(str_replace('_', ' ', $blockName));
                    $defaultCategorySearchStrings[$categoryName] = [];
                    $extraMatches[strtolower($categoryName)] = [$blockName];
                }
            }
        }

        // if a default category is not found create it
        $order = 0;
        foreach ($defaultCategorySearchStrings as $defaultCategory => $searchStrings) {
            $order += 20;
            if (!array_key_exists($defaultCategory, $defaultCategoryIds) && !array_key_exists(strtolower($defaultCategory), $this->_blockCategoriesByName)) {
                $newBlockCategory = new BlockCategory;
                $newBlockCategory->name = ucwords($defaultCategory);
                $newBlockCategory->order = $order;
                $newBlockCategory->save();
                $this->_blockCategoriesByName[strtolower($newBlockCategory->name)] = $newBlockCategory;
                $defaultCategoryIds[$defaultCategory] = $newBlockCategory->id;
            }
        }

        // find closest matching default category based on block name
        $categoryMatches = [
            $defaultCategoryIds['header'] => ['header_html', 'head', 'logo', 'phone', 'email'],
            $defaultCategoryIds['banner'] => ['banner', 'carousel'],
            $defaultCategoryIds['footer'] => ['footer_html', 'foot', 'address', 'copyright'],
            $defaultCategoryIds['seo'] => ['meta'],
        ];
        foreach ($extraMatches as $categoryName => $searchStrings) {
            $categoryMatches[$this->_blockCategoriesByName[$categoryName]->id] = $searchStrings;
        }
        foreach ($this->_blockData as $blockName => &$blockData) {
            if (!array_key_exists('category_id', $blockData)) {
                $blockData['category_id'] = $defaultCategoryIds['main'];
                foreach ($categoryMatches as $categoryId => $searchStrings) {
                    foreach ($searchStrings as $searchString) {
                        if (stristr($blockName, $searchString)) {
                            $blockData['category_id'] = $categoryId;
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function _loadMissingIsGlobalDataFromDb()
    {
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');
        foreach ($this->_blockData as $blockName => $blockData) {
            if (!array_key_exists($blockName, $this->_blockGlobals)) {
                $this->_blockGlobals[$blockName] = [];
            }
            $block = Block::preload($blockName);
            if ($themeBlocks->has($block->id)) {
                foreach (['show_in_global', 'show_in_pages'] as $field) {
                    if (!array_key_exists($field, $this->_blockGlobals[$blockName])) {
                        $this->_blockGlobals[$blockName][$field] = $themeBlocks[$block->id]->$field;
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function _isGlobalGuess()
    {
        $totalTemplates = count($this->_templateList);
        foreach ($this->_blockData as $blockName => $blockData) {
            if (!array_key_exists($blockName, $this->_blockGlobals)) {
                $this->_blockGlobals[$blockName] = [];
            }
            if (!array_key_exists('show_in_global', $this->_blockGlobals[$blockName])) {
                $templates = array_key_exists($blockName, $this->_blockTemplates) ? count($this->_blockTemplates[$blockName]) : 0;
                $this->_blockGlobals[$blockName]['show_in_global'] = ((count($templates) / $totalTemplates) >= 0.7) ? 1 : 0;
            }
            if (!array_key_exists('show_in_pages', $this->_blockGlobals[$blockName])) {
                $this->_blockGlobals[$blockName]['show_in_pages'] = 0;
            }
        }
    }


    /**
     *
     */
    public function getImportTemplateData()
    {
        // TODO array of new/existing templates
    }

    /**
     * @return array
     */
    public function getImportBlockData()
    {
        return $this->_blocksCollection->getAggregatedBlocks();
    }

    /**
     * @return array
     */
    public function getCurrentBlockData()
    {
        $currentBlocks = [];
        $globalSettings = ['show_in_global', 'show_in_pages'];
        $themeTemplates = Template::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('id');
        $themeTemplateIds = $themeTemplates->keys()->toArray();
        $templateBlocks = TemplateBlock::whereIn('template_id', $themeTemplateIds)->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');
        foreach ($themeBlocks as $blockId => $themeBlock) {
            $currentBlock = Block::preload($blockId);
            if ($currentBlock->exists) {
                $currentBlocks[$currentBlock->name] = [
                    'block' => $currentBlock->getAttributes(),
                    'templates' =>array_diff($themeTemplateIds, explode(',', $themeBlock->exclude_templates))
                ];
                foreach ($globalSettings as $globalSetting) {
                    $currentBlocks[$currentBlock->name]['global'][$globalSetting] = $themeBlock->$globalSetting;
                }
            }
        }
        foreach ($templateBlocks as $blockId => $templateBlock) {
            $currentBlock = Block::preload($blockId);
            if ($currentBlock->exists && !array_key_exists($currentBlock->name, $currentBlocks)) {
                $currentBlocks[$currentBlock->name] = [
                    'block' => $currentBlock->getAttributes(),
                    'templates' => $templateBlock->keyBy('template_id')->keys()->toArray()
                ];
            }
        }
        $blockIds = array_map(function($currentBlock) {
            return $currentBlock['block']['id'];
        }, $currentBlocks);
        $currentRepeaters = BlockRepeater::whereIn('block_id', $blockIds)->get()->keyBy('block_id');
        foreach ($currentRepeaters as $blockId => $currentRepeater) {
            $currentRepeaterBlock = Block::preload($blockId);
            if ($currentRepeaterBlock->exists) {
                $currentRepeaterChildBlockIds = explode(',', $currentRepeater->blocks);
                $currentRepeaterChildBlockNames = [];
                foreach ($currentRepeaterChildBlockIds as $currentRepeaterChildBlockId) {
                    $currentRepeaterChildBlock = Block::preload($currentRepeaterChildBlockId);
                    if ($currentRepeaterChildBlock->exists) {
                        $currentRepeaterChildBlockNames[] = $currentRepeaterChildBlock->name;
                        if (!array_key_exists($currentRepeaterChildBlock->name, $currentBlocks)) {
                            $currentBlocks[$currentRepeaterChildBlock->name]['block'] = $currentRepeaterChildBlock->getAttributes();
                        }
                        if (!array_key_exists('other_view', $currentBlocks[$currentRepeaterChildBlock->name])) {
                            $currentBlocks[$currentRepeaterChildBlock->name]['other_view'] = [];
                        }
                        if (!array_key_exists('repeaters', $currentBlocks[$currentRepeaterChildBlock->name]['other_view'])) {
                            $currentBlocks[$currentRepeaterChildBlock->name]['other_view']['repeaters'] = [];
                        }
                        $currentBlocks[$currentRepeaterChildBlock->name]['other_view']['repeaters'][] = $currentRepeaterBlock->name;
                    }
                }
                $currentBlocks[$currentRepeaterBlock->name]['other_view']['repeater_children'] = $currentRepeaterChildBlockNames;
            }
        }
        foreach ($currentBlocks as $blockName => $dataGroups) {
            if (!array_key_exists('global', $dataGroups)) {
                foreach ($globalSettings as $globalSetting) {
                    $currentBlocks[$blockName]['global'][$globalSetting] = 0;
                }
            }
            if (!array_key_exists('templates', $dataGroups)) {
                $currentBlocks[$blockName]['templates'] = [];
            } else {
                $templateNames = [];
                foreach ($dataGroups['templates'] as $templateId) {
                    if ($themeTemplates->has($templateId)) {
                        $templateNames[] = $themeTemplates[$templateId]->template;
                    }
                }
                $currentBlocks[$blockName]['templates'] = $templateNames;
            }
            if (!array_key_exists('other_view', $dataGroups)) {
                $currentBlocks[$blockName]['other_view'] = [];
            }
            unset($currentBlocks[$blockName]['block']['created_at']);
            unset($currentBlocks[$blockName]['block']['updated_at']);
        }
        return $currentBlocks;
    }

    /**
     *
     */
    public function getImportBlockChanges()
    {
        $currentBlocks = $this->getCurrentBlockData();
        $importBlocks = $this->getImportBlockData();
        $blockChanges = [];
        // all imported block to create or update
        foreach ($importBlocks as $blockName => $dataGroups) {
            $blockChanges[$blockName] = [];
            foreach ($dataGroups as $dataGroup => $importBlockData) {
                $blockChanges[$blockName][$dataGroup] = [];
                if (array_key_exists($blockName, $currentBlocks)) {
                    $currentBlockData = array_key_exists($dataGroup, $currentBlocks[$blockName]) ? $currentBlocks[$blockName][$dataGroup] : [];
                    if (in_array($dataGroup, ['block', 'global'])) {
                        foreach ($importBlockData as $field => $value) {
                            if ($currentBlockData[$field] != $value) {
                                $blockChanges[$blockName]['display_class'] = 'update';
                                if ($dataGroup == 'global') {
                                    $blockChanges[$blockName]['update_templates'] = 1;
                                }
                            }
                        }
                    }
                    if ($dataGroup == 'templates' && (array_diff($currentBlockData, $importBlockData) || array_diff($importBlockData, $currentBlockData))) {
                        $blockChanges[$blockName]['display_class'] = 'update';
                        $blockChanges[$blockName]['update_templates'] = 1;
                    }
                    if ($dataGroup == 'other_view') {
                        foreach (['repeater_children'] as $attribute) {
                            $currentRepeaterData = array_key_exists($attribute, $currentBlockData) ? $currentBlockData[$attribute] : [];
                            $importRepeaterData = array_key_exists($attribute, $importBlockData) ? $importBlockData[$attribute] : [];
                            if ((array_diff($currentRepeaterData, $importRepeaterData) || array_diff($importRepeaterData, $currentRepeaterData))) {
                                $blockChanges[$blockName]['display_class'] = 'update';
                                $blockChanges[$blockName]['update_templates'] = 1;
                            }
                        }
                    }
                } else {
                    $blockChanges[$blockName]['display_class'] = 'new';
                    $currentBlockData = [];
                    if ($dataGroup == 'block') {
                        $block = Block::preload($blockName);
                        if ($block->exists) {
                            $currentBlockData = $block->getAttributes();
                            unset($currentBlockData['created_at']);
                            unset($currentBlockData['updated_at']);
                        }
                    }
                }
                $blockChanges[$blockName][$dataGroup] = ['import' => $importBlockData, 'current' => $currentBlockData];
            }
        }
        // any blocks not found in the import
        foreach ($currentBlocks as $blockName => $dataGroups) {
            if (!array_key_exists($blockName, $blockChanges)) {
                foreach ($dataGroups as $dataGroup => $currentBlockData) {
                    $blockChanges[$blockName]['display_class'] = 'delete';
                    if ($dataGroup == 'templates' && !empty($currentBlockData)) {
                        $blockChanges[$blockName]['update_templates'] = 1;
                    }
                    $blockChanges[$blockName][$dataGroup] = ['import' => [], 'current' => $currentBlockData];
                }
            }
        }
        // update defaults and set info class on blocks found in views that could not be matched to a template
        foreach ($blockChanges as $blockName => $dataGroups) {
            if (!array_key_exists('display_class', $dataGroups)) {
                foreach ($dataGroups['other_view'] as $dataSet => $otherView) {
                    if (!empty($otherView['category']) || !empty($otherView['other_page'])) {
                        $blockChanges[$blockName]['display_class'] = 'info';
                    }
                }
            }
            $canUpdateTemplates = -1;
            foreach ($dataGroups['templates'] as $dataSet => $templates) {
                if (!empty($templates)) {
                    $canUpdateTemplates = 0;
                }
                $blockChanges[$blockName]['other_view'][$dataSet] += [
                    'repeaters' => [],
                    'repeater_children' => []
                ];
            }
            $blockChanges[$blockName]['display_class'] = array_key_exists('display_class', $dataGroups) ? $dataGroups['display_class'] : 'none';
            $blockChanges[$blockName]['update_templates'] = array_key_exists('update_templates', $dataGroups) ? $dataGroups['update_templates'] : $canUpdateTemplates;
        }
        uasort($blockChanges, [$this, '_orderBlockChanges']);
        return $blockChanges;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    protected function _orderBlockChanges($a, $b)
    {
        $byDisplayClass = ['new' => 1, 'update' => 2, 'delete' => 3, 'info' => 4, 'none' => 5];
        if ($byDisplayClass[$a['display_class']] == $byDisplayClass[$b['display_class']]) {
            if ($a['update_templates'] >= 0 && $b['update_templates'] >= 0) {
                $nameA = !empty($a['block']['import']['name']) ? $a['block']['import']['name'] : $a['block']['current']['name'];
                $nameB = !empty($b['block']['import']['name']) ? $b['block']['import']['name'] : $b['block']['current']['name'];
                return strcmp($nameA, $nameB);
            }
            return ($a['update_templates'] < $b['update_templates']) ? 1 : -1;
        }
        return ($byDisplayClass[$a['display_class']] > $byDisplayClass[$b['display_class']]) ? 1 : -1;
    }

    /**
     *
     */
    public function save()
    {
        $this->_saveBlockData();
        $this->_saveBlockTemplates();
        $this->_saveBlockRepeaters();

        // run import for select blocks (can only be run after blocks have been saved as it saves a block_id)
        $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
        $selectOptionsImport->setTheme($this->_additionalData['theme'])->run();
    }

    /**
     *
     */
    protected function _saveBlockData()
    {
        foreach ($this->_blockData as $blockName => $blockData) {
            $block = Block::preload($blockName);
            foreach ($blockData as $field => $value) {
                $block->$field = $value;
            }
            $block->save();
            $this->_blockData[$blockName]['id'] = $block->id;
        }
    }

    /**
     *
     */
    protected function _saveBlockTemplates()
    {
        $themeTemplates = $this->_saveNewTemplates();
        $themeTemplateIds = $themeTemplates->keyBy('id')->keys()->toArray();
        $templateBlocks = TemplateBlock::whereIn('template_id', $themeTemplateIds)->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');

        foreach ($this->_blockData as $blockName => $blockData) {
            $newTemplates = array_key_exists($blockName, $this->_blockTemplates) ? $this->_blockTemplates[$blockName] : [];
            $newTemplateIds = array_map(function ($template) use ($themeTemplates) {
                return $themeTemplates[$template]->id;
            }, $newTemplates);
            if ($this->_blockGlobals[$blockName]['show_in_global'] || $this->_blockGlobals[$blockName]['show_in_pages']) {
                // save as a theme block (& remove template blocks)
                $themeBlock = $themeBlocks->has($blockData['id']) ? $themeBlocks[$blockData['id']] : new ThemeBlock;
                $themeBlock->theme_id = $this->_additionalData['theme']->id;
                $themeBlock->block_id = $blockData['id'];
                $themeBlock->exclude_templates = implode(',', array_diff($themeTemplateIds, $newTemplateIds));
                $themeBlock->show_in_global = $this->_blockGlobals[$blockName]['show_in_global'];
                $themeBlock->show_in_pages = $this->_blockGlobals[$blockName]['show_in_pages'];
                $themeBlock->save();
                TemplateBlock::where('block_id', '=', $blockData['id'])->whereIn('template_id', $themeTemplateIds)->delete();
            } else {
                // save a template blocks (& remove theme block)
                $existingTemplateIds = $templateBlocks->has($blockData['id']) ? $templateBlocks[$blockData['id']]->keyBy('template_id')->keys()->toArray() : [];
                $addTemplateIds = array_diff($newTemplateIds, $existingTemplateIds);
                foreach ($addTemplateIds as $templateId) {
                    $newTemplateBlock = new TemplateBlock;
                    $newTemplateBlock->block_id = $blockData['id'];
                    $newTemplateBlock->template_id = $templateId;
                    $newTemplateBlock->save();
                }
                $deleteTemplateIds = array_diff($existingTemplateIds, $newTemplateIds);
                TemplateBlock::where('block_id', '=', $blockData['id'])->whereIn('template_id', $deleteTemplateIds)->delete();
                ThemeBlock::where('block_id', '=', $blockData['id'])->where('theme_id', '=', $this->_additionalData['theme']->id)->delete();
            }
        }
    }

    /**
     *
     */
    protected function _saveNewTemplates()
    {
        $themeTemplates = Template::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('template');
        foreach ($this->_blockTemplates as $block => $templates) {
            foreach ($templates as $template) {
                if (!$themeTemplates->has($template)) {
                    $newTemplate = new Template;
                    $newTemplate->theme_id = $this->_additionalData['theme']->id;
                    $newTemplate->template = $template;
                    $newTemplate->label = ucwords(str_replace('_', ' ', $template)) . ' Template';
                    $newTemplate->child_template = 0;
                    $newTemplate->hidden = 0;
                    $newTemplate->save();
                    $themeTemplates[$template] = $newTemplate;
                }
            }
        }
        return $themeTemplates;
    }

    /**
     *
     */
    protected function _saveBlockRepeaters()
    {
        $existingRepeaters = BlockRepeater::get()->keyBy('block_id');
        foreach ($this->_blockOtherViewData['repeater'] as $repeaterBlockName => $childBlockNames) {
            $newRepeaterData = $existingRepeaters->has($this->_blockData[$repeaterBlockName]['id']) ? $existingRepeaters[$this->_blockData[$repeaterBlockName]['id']] : new BlockRepeater;
            $newRepeaterData->block_id = $this->_blockData[$repeaterBlockName]['id'];
            $newRepeaterData->blocks = implode(',', array_map(function($childBlockName) {
                return $this->_blockData[$childBlockName]['id'];
            }, $childBlockNames));
            $newRepeaterData->save();
        }
    }

}