<?php namespace CoasterCms\Libraries\Import;

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
    protected $_blockOtherViews;

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
        if (!array_key_exists('theme', $this->_additionalData) || !is_a($this->_additionalData['theme'], Theme::class)) {
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
        $this->_blockData = [];
        $this->_blockGlobals = [];
        $this->_blockTemplates = [];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentBlockName = $this->_toLowerTrim($this->_importCurrentRow['Block Name']);
        $this->_blockData[$this->_currentBlockName] = [];
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        if ($importFieldData !== '') {
            $this->_blockData[$this->_currentBlockName][$importInfo['mapTo']] = $importFieldData;
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
        if (!array_key_exists($this->_currentBlockName, $this->_blockTemplates)) {
            $this->_blockTemplates[$this->_currentBlockName] = [];
        }
        if ($importFieldData !== '') {
            $templates = ($importFieldData == '*') ? $this->_templateList : explode(',', $importFieldData);
            $this->_blockTemplates[$this->_currentBlockName] = array_unique(array_merge($this->_blockTemplates[$this->_currentBlockName], $templates));
        }
    }

    /**
     * @param string $importFieldData
     * @param string $globalSetting
     */
    protected function _mapGlobal($importFieldData, $globalSetting = '')
    {
        if ($importFieldData !== '') {
            if (!array_key_exists($this->_currentBlockName, $this->_blockGlobals)) {
                $this->_blockGlobals[$this->_currentBlockName] = [];
            }
            $this->_blockGlobals[$this->_currentBlockName][$globalSetting] = $this->_toBool($importFieldData) ? 1 : 0;
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
        PageBuilder::setClass(
            PageBuilder\ThemeBuilderInstance::class,
            [$this->_blockData, $this->_blockTemplates],
            PageLoaderDummy::class,
            [$this->_additionalData['theme']->theme]
        );
        $this->_renderThemeFiles();
        $this->_renderCsvBlocks();

        // load extra data from rendering blocks
        $this->_blockData = PageBuilder::getData('blockData');
        $this->_blockTemplates = PageBuilder::getData('blockTemplates');
        $this->_blockOtherViews = PageBuilder::getData('blockOtherViews'); // blocks not directly linked to a template

        // save all imported block / template / repeater data
        $this->_saveBlockData();
        $this->_saveBlockTemplates();
        $this->_saveBlockRepeaters();

        $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
        $selectOptionsImport->setTheme($this->_additionalData['theme'])->run();
    }

    /**
     * Get all extra block data from theme files
     */
    protected function _renderThemeFiles()
    {
        if ($this->_templateList) {
            foreach ($this->_templateList as $templateName) {
                PageBuilder::setData('template', $templateName);
                View::make('themes.' . $this->_additionalData['theme']->theme . '.templates.' . $templateName)->render();
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
        foreach ($this->_blockData as $blockName => $blockData) {
            PageBuilder::block($blockName);
        }
    }

    /**
     *
     */
    protected function _saveBlockData()
    {
        // fill in and missing block data
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
    protected function _loadMissingBlockDataFromDb()
    {
        foreach ($this->_blockData as $blockName => &$blockData) {
            $block = Block::preload($blockName);
            if ($block->exists) {
                foreach ($block->getAttributes() as $field => $value) {
                    if (!array_key_exists($field, $blockData)) {
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
    protected function _saveBlockTemplates()
    {
        $this->_isGlobalGuess();
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
    protected function _isGlobalGuess()
    {
        // set global guesses
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
        foreach ($this->_blockOtherViews['repeater'] as $repeaterBlockName => $childBlockNames) {
            $newRepeaterData = $existingRepeaters->has($this->_blockData[$repeaterBlockName]['id']) ? $existingRepeaters[$this->_blockData[$repeaterBlockName]['id']] : new BlockRepeater;
            $newRepeaterData->block_id = $this->_blockData[$repeaterBlockName]['id'];
            $newRepeaterData->blocks = implode(',', array_map(function($childBlockName) {
                return $this->_blockData[$childBlockName]['id'];
            }, $childBlockNames));
            $newRepeaterData->save();
        }
    }

}