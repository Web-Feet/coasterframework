<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Admin\Import\BlocksCollection;
use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\Template;
use CoasterCms\Models\TemplateBlock;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeBlock;
use Illuminate\Database\Eloquent\Collection;
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
     * @var BlocksCollection
     */
    protected $_blocksCollection;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'blocks.csv';

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
     * BlocksImport constructor.
     * @param string $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile = '', $requiredFile = false)
    {
        parent::__construct($importFile, $requiredFile);
        $this->_blocksCollection = new BlocksCollection();
        $this->_blocksCollection->setScope('csv');
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
        if ($importFieldData !== '' && $this->_currentBlockName !== '') {
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
        if ($importFieldData !== '' && $this->_currentBlockName !== '') {
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
        if ($importFieldData !== '' && $this->_currentBlockName !== '') {
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
        // load data from db for blocks in theme, doesn't have priority over data found in theme files but can be useful in guesses
        $this->_loadDbData();
        // theme render (includes type guess in themebuilder)
        $this->_renderThemeFiles();
        // load database repeater data (quicker to do at end as additional db blocks may have been loaded in the render above)
        $this->_loadDbRepeaterData();
        // guess the missing data
        $this->_guessMissingData();
    }

    /**
     * @param string $scope
     */
    protected function _loadDbData($scope = 'db')
    {
        $this->_blocksCollection->setScope($scope);
        $themeTemplates = Template::where('theme_id', '=', $this->_additionalData['theme']->id)->get();
        $templateBlocks = TemplateBlock::whereIn('template_id', $themeTemplates->pluck('id')->toArray())->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');
        foreach ($themeBlocks as $blockId => $themeBlock) {
            $currentBlock = Block::preload($blockId);
            if ($currentBlock->exists) {
                $this->_blocksCollection->getBlock($currentBlock->name)
                    ->setBlockData($currentBlock->getAttributes())
                    ->setGlobalData($themeBlock->getAttributes())
                    ->addTemplates($themeTemplates->except(explode(',', $themeBlock->exclude_templates))->pluck('template')->toArray());
            }
        }
        foreach ($templateBlocks as $blockId => $templateBlock) {
            $currentBlock = Block::preload($blockId);
            if (!$themeBlocks->has($blockId) && $currentBlock->exists) {
                $this->_blocksCollection->getBlock($currentBlock->name)
                    ->setBlockData($currentBlock->getAttributes())
                    ->setGlobalData(['show_in_global' => 0, 'show_in_pages' => 0])
                    ->addTemplates($themeTemplates->whereIn('id', $templateBlock->pluck('template_id')->toArray())->pluck('template')->toArray());
            }
        }
    }

    /**
     * @param string $scope
     */
    protected function _loadDbRepeaterData($scope = 'db')
    {
        $this->_blocksCollection->setScope($scope);
        $blockIds = array_map(function(\CoasterCms\Helpers\Admin\Import\Block $currentBlock) {
            return $currentBlock->blockData['id'];
        }, $this->_blocksCollection->getBlocks('db'));
        $currentRepeaters = BlockRepeater::whereIn('block_id', $blockIds)->get();
        foreach ($currentRepeaters as $blockId => $currentRepeater) {
            $currentRepeaterBlock = Block::preload($currentRepeater->block_id);
            if ($currentRepeaterBlock->exists) {
                $currentRepeaterChildBlockIds = explode(',', $currentRepeater->blocks);
                $currentRepeaterChildBlockNames = [];
                foreach ($currentRepeaterChildBlockIds as $currentRepeaterChildBlockId) {
                    $currentRepeaterChildBlock = Block::preload($currentRepeaterChildBlockId);
                    if ($currentRepeaterChildBlock->exists) {
                        $currentRepeaterChildBlockNames[] = $currentRepeaterChildBlock->name;
                        $this->_blocksCollection->getBlock($currentRepeaterChildBlock->name)
                            ->setBlockData($currentRepeaterChildBlock->getAttributes())
                            ->setGlobalData(['show_in_global' => 0, 'show_in_pages' => 0])
                            ->addRepeaterBlocks($currentRepeaterBlock->name);
                    }
                }
                $this->_blocksCollection->getBlock($currentRepeaterBlock->name)->addRepeaterChildBlocks($currentRepeaterChildBlockNames);
            }
        }
    }

    /**
     * Get all extra block data from theme files
     * @param string $scope
     */
    protected function _renderThemeFiles($scope = 'file')
    {
        $this->_blocksCollection->setScope($scope);

        PageBuilder::setClass(
            PageBuilder\ThemeBuilderInstance::class,
            [$this->_blocksCollection],
            PageLoaderDummy::class,
            [$this->_additionalData['theme']->theme]
        );
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

        // make sure every csv block is rendered & better after theme render as there is less context
        $this->_renderCsvBlocks();
    }

    /**
     * Run all blocks from import csv as they may not have be found and rendered in the theme files
     */
    protected function _renderCsvBlocks()
    {
        PageBuilder::setData('template', '');
        foreach ($this->_blocksCollection->getBlocks('csv') as $blockName => $importBlock) {
            PageBuilder::block($blockName);
        }
    }

    /**
     * @param string $scope
     */
    protected function _guessMissingData($scope = 'guess')
    {
        $this->_blocksCollection->setScope($scope);
        $allBlockData = $this->_blocksCollection->getAggregatedBlocks();
        foreach ($allBlockData as $blockName => $importBlock) {
            $this->_blocksCollection->getBlock($blockName)
                ->setBlockData([
                    'label' => ucwords(str_replace('_', ' ', $blockName)),
                    'active' => 1,
                    'search_weight' => 1,
                    'note' => '',
                    'order' => 100
                ])
                ->setGlobalData([
                    'show_in_global' => ((count($importBlock->templates) / count($this->_templateList)) >= 0.7) ? 1 : 0,
                    'show_in_pages' => 0
                ]);
        }
        $this->_categoryIdGuess($allBlockData);
        // TODO set order guesses
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     */
    protected function _categoryIdGuess($allBlockData)
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
        foreach ($allBlockData as $blockName => $importBlock) {
            if (!array_key_exists('category_id', $importBlock->blockData) && $importBlock->blockData['type'] == 'repeater') {
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
        foreach ($allBlockData as $blockName => $importBlock) {
            if (!array_key_exists('category_id', $importBlock->blockData)) {
                $guessedCategoryId = $defaultCategoryIds['main'];
                foreach ($categoryMatches as $categoryId => $searchStrings) {
                    foreach ($searchStrings as $searchString) {
                        if (stristr($blockName, $searchString)) {
                            $guessedCategoryId = $categoryId;
                        }
                    }
                }
                $this->_blocksCollection->getBlock($blockName)->setBlockData(['category_id' => $guessedCategoryId]);
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
     * @return BlocksCollection
     */
    public function getBlocksCollection()
    {
        return $this->_blocksCollection;
    }

    /**
     * @param array $updateTemplates
     */
    public function save($updateTemplates)
    {
        $allBlockData = $this->_saveBlockData($this->_blocksCollection->getAggregatedBlocks()); // should have block ids after
        $allBlockData = array_intersect_key($allBlockData, array_filter($updateTemplates));
        $this->_saveBlockTemplates($allBlockData);
        $this->_saveBlockRepeaters($allBlockData);

        // run import for select blocks (can only be run after blocks have been saved as it saves a block_id)
        $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
        $selectOptionsImport->setTheme($this->_additionalData['theme'])->run();
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     * @return \CoasterCms\Helpers\Admin\Import\Block[]
     */
    protected function _saveBlockData($allBlockData)
    {
        foreach ($allBlockData as $blockName => $importBlock) {
            $block = Block::preload($blockName);
            foreach ($importBlock->blockData as $field => $value) {
                $block->$field = $value;
            }
            $block->save();
            $this->_blocksCollection->getBlock($blockName, 'db')->setBlockData($block->getAttributes());
        }
        return $this->_blocksCollection->getAggregatedBlocks(); // return regenerated aggregated blocks
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     */
    protected function _saveBlockTemplates($allBlockData)
    {
        $themeTemplates = $this->_saveNewTemplates($allBlockData);
        $themeTemplateIds = $themeTemplates->pluck('id')->toArray();
        $templateBlocks = TemplateBlock::whereIn('template_id', $themeTemplateIds)->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');

        foreach ($allBlockData as $blockName => $importBlock) {
            $newTemplateIds = array_map(function ($template) use ($themeTemplates) {
                return $themeTemplates[$template]->id;
            }, $importBlock->templates);
            if ($importBlock->globalData['show_in_global'] || $importBlock->globalData['show_in_pages']) {
                // save as a theme block (& remove template blocks)
                $themeBlock = $themeBlocks->has($importBlock->blockData['id']) ? $themeBlocks[$importBlock->blockData['id']] : new ThemeBlock;
                $themeBlock->theme_id = $this->_additionalData['theme']->id;
                $themeBlock->block_id = $importBlock->blockData['id'];
                $themeBlock->exclude_templates = implode(',', array_diff($themeTemplateIds, $newTemplateIds));
                $themeBlock->show_in_global = $importBlock->globalData['show_in_global'];
                $themeBlock->show_in_pages = $importBlock->globalData['show_in_pages'];
                $themeBlock->save();
                TemplateBlock::where('block_id', '=', $importBlock->blockData['id'])->whereIn('template_id', $themeTemplateIds)->delete();
            } else {
                // save a template blocks (& remove theme block)
                $existingTemplateIds = $templateBlocks->has($importBlock->blockData['id']) ? $templateBlocks[$importBlock->blockData['id']]->pluck('template_id')->toArray() : [];
                $addTemplateIds = array_diff($newTemplateIds, $existingTemplateIds);
                foreach ($addTemplateIds as $templateId) {
                    $newTemplateBlock = new TemplateBlock;
                    $newTemplateBlock->block_id = $importBlock->blockData['id'];
                    $newTemplateBlock->template_id = $templateId;
                    $newTemplateBlock->save();
                }
                $deleteTemplateIds = array_diff($existingTemplateIds, $newTemplateIds);
                TemplateBlock::where('block_id', '=', $importBlock->blockData['id'])->whereIn('template_id', $deleteTemplateIds)->delete();
                ThemeBlock::where('block_id', '=', $importBlock->blockData['id'])->where('theme_id', '=', $this->_additionalData['theme']->id)->delete();
            }
        }
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     * @return Collection
     */
    protected function _saveNewTemplates($allBlockData)
    {
        $themeTemplates = Template::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('template');
        foreach ($allBlockData as $blockName => $importBlock) {
            foreach ($importBlock->templates as $template) {
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
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     */
    protected function _saveBlockRepeaters($allBlockData)
    {
        $existingRepeaters = BlockRepeater::get()->keyBy('block_id');
        foreach ($allBlockData as $blockName => $importBlock) {
            $newRepeaterData = $existingRepeaters->has($importBlock->blockData['id']) ? $existingRepeaters[$importBlock->blockData['id']] : new BlockRepeater;
            $newRepeaterData->block_id = $importBlock->blockData['id'];
            $newRepeaterData->blocks = implode(',', array_map(function ($childBlockName) {
                return $this->_blocksCollection->getBlock($childBlockName, 'db')->blockData['id'];
            }, $importBlock->repeaterChildBlocks));
            if ($newRepeaterData->blocks) {
                $newRepeaterData->save();
            } else {
                $newRepeaterData->delete();
            }
        }
    }

    /**
     *
     */
    public function cleanCsv()
    {
       // TODO remove data found in other scopes from csv
        $scopes = $this->_blocksCollection->getScopes();
        unset($scopes['csv']);
        $importBlocks = $this->_blocksCollection->getAggregatedBlocks(array_keys($scopes));
        $csvBlocks = $this->_blocksCollection->getBlocks('csv');




        //Directory::remove(pathinfo($this->_importFile, PATHINFO_DIRNAME) . '/blocks');
    }

}