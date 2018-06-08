<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Admin\Import\BlocksCollection;
use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder\ThemeBuilderInstance;
use CoasterCms\Libraries\Export\BlocksExport;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeBlock;
use CoasterCms\Models\ThemeTemplate;
use CoasterCms\Models\ThemeTemplateBlock;
use Illuminate\Database\Eloquent\Collection;
use PageBuilder;
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
    protected $_renderedOrders;

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
                'mapTo' => ['setBlockData', 'name'],
                'mapFn' => '_toLowerTrim',
                'validate' => 'required'
            ],
            'Block Label' => [
                'mapTo' => ['setBlockData', 'label']
            ],
            'Block Note' => [
                'mapTo' => ['setBlockData', 'note']
            ],
            'Block Category' => [
                'mapTo' => ['setBlockData', 'category_id'],
                'mapFn' => '_toCategoryId'
            ],
            'Block Type' => [
                'mapTo' => ['setBlockData', 'type']
            ],
            'Global (show in site-wide)' => [
                'mapTo' => ['setGlobalData', 'show_in_global'],
                'mapFn' => '_toBoolInt'
            ],
            'Global (show in pages)' => [
                'mapTo' => ['setGlobalData', 'show_in_pages'],
                'mapFn' => '_toBoolInt'
            ],
            'Templates' => [
                'mapTo' => ['addTemplates'],
                'mapFn' => '_mapTemplates'
            ],
            'Block Order' => [
                'mapTo' => ['setBlockData', 'order']
            ],
            'Block Active' => [
                'mapTo' => ['setBlockData', 'active']
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
            $this->_importErrors[] = new \Exception('Theme must be specified before blocks can be imported');
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
        $this->_importErrors = array_merge($this->_importErrors, $categoryImport->getErrors());
        $this->_blockCategoriesByName = $categoryImport->getBlockCategoriesByName();
        $formRulesImport = new \CoasterCms\Libraries\Import\Blocks\FormRulesImport();
        $formRulesImport->setTheme($this->_additionalData['theme'])->run();
        $this->_importErrors = array_merge($this->_importErrors, $formRulesImport->getErrors());
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
            $importBlock = $this->_blocksCollection->getBlock($this->_currentBlockName);
            if (count($importInfo['mapTo']) == 2) {
                list($function, $field) = $importInfo['mapTo'];
                $parameter = [$field => $importFieldData];
            } else {
                $function = reset($importInfo['mapTo']);
                $parameter = $importFieldData;
            }
            $importBlock->$function($parameter);
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
     * @return string
     */
    protected function _mapTemplates($importFieldData)
    {
        if ($importFieldData !== '' && $this->_currentBlockName !== '') {
            $templates = explode(',', $importFieldData);
            $templates = in_array('*', $templates) ? array_merge($this->_templateList, $templates) : $templates;
            $templates = array_flip(array_unique($templates));
            foreach ($templates as $template => $i) {
                if ($template == '*') {
                    unset($templates[$template]);
                } elseif (strpos($template, '-') === 0) {
                    unset($templates[$template]);
                    unset($templates[substr($template, 1)]);
                }
            }
            $templates = array_flip($templates);
            $templates = array_filter($templates, function($templateName) {
                return View::exists('themes.' . $this->_additionalData['theme']->theme . '.templates.' . $templateName);
            });
            $this->_templateList = array_unique(array_merge($this->_templateList, $templates));
            return $templates;
        }
        return '';
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
        $allTemplates = Template::get();
        $themeTemplates = ThemeTemplate::where('theme_id', '=', $this->_additionalData['theme']->id)->get();
        $themeTemplateBlocks = ThemeTemplateBlock::whereIn('theme_template_id', $themeTemplates->pluck('id')->toArray())->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');
        foreach ($themeBlocks as $blockId => $themeBlock) {
            $currentBlock = Block::preload($blockId);
            if ($currentBlock->exists) {
                $templateIds = $themeTemplates->whereNotIn('template_id', explode(',', $themeBlock->exclude_templates))->pluck('template_id')->toArray();
                if ($templateIds) {
                    $this->_blocksCollection->getBlock($currentBlock->name)
                        ->setBlockData($currentBlock->getAttributes())
                        ->setGlobalData($themeBlock->getAttributes())
                        ->addTemplates($allTemplates->whereIn('id', $templateIds)->pluck('template')->toArray());
                } else {
                    $themeBlock->delete();
                }
            }
        }
        foreach ($themeTemplateBlocks as $blockId => $themeTemplateBlock) {
            $currentBlock = Block::preload($blockId);
            if (!$themeBlocks->has($blockId) && $currentBlock->exists) {
                $templateIds = $themeTemplates->whereIn('id', $themeTemplateBlock->pluck('theme_template_id')->toArray())->pluck('template_id')->toArray();
                $this->_blocksCollection->getBlock($currentBlock->name)
                    ->setBlockData($currentBlock->getAttributes())
                    ->setGlobalData(['show_in_global' => 0, 'show_in_pages' => 0])
                    ->addTemplates($allTemplates->whereIn('id', $templateIds)->pluck('template')->toArray());
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
        }, $this->_blocksCollection->getBlocks($scope));
        $currentRepeaters = BlockRepeater::whereIn('block_id', BlockRepeater::addChildBlockIds($blockIds))->get();
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

        $themeBuilder = PageBuilder::make(ThemeBuilderInstance::class, [new PageLoaderDummy($this->_additionalData['theme']->theme), $this->_blocksCollection]);
        if ($this->_templateList) {
            foreach ($this->_templateList as $templateName) {
                $templateView = 'themes.' . $this->_additionalData['theme']->theme . '.templates.' . $templateName;
                if (View::exists($templateView)) {
                    $themeBuilder->setData('template', $templateName);
                    $themeBuilder->setRenderPath([['template' => $templateName]]);
                    try {
                        View::make($templateView)->render();
                    } catch (\Exception $e) {
                        $this->_importErrors[] = $e;
                    }
                }
            }
            $this->_renderedOrders = $themeBuilder->getOrders();
        }

        // make sure every csv block is rendered & better after theme render as there is less context
        $this->_renderCsvBlocks($themeBuilder);
    }

    /**
     * Run all blocks from import csv as they may not have be found and rendered in the theme files
     * @param ThemeBuilderInstance $themeBuilder
     */
    protected function _renderCsvBlocks($themeBuilder)
    {
        $themeBuilder->setData('template', '');
        foreach ($this->_blocksCollection->getBlocks('csv') as $blockName => $importBlock) {
            $themeBuilder->block($blockName);
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
                    'note' => ''
                ])
                ->setGlobalData([
                    'show_in_global' => ((count($importBlock->templates) / count($this->_templateList)) >= 0.7) ? 1 : 0,
                    'show_in_pages' => 0
                ]);
        }
        $this->_categoryIdGuess($allBlockData);
        $allBlockData = $this->_blocksCollection->getAggregatedBlocks();
        $this->_orderGuess($allBlockData);
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     */
    protected function _orderGuess($allBlockData)
    {
        $orders = [];
        foreach ($this->_renderedOrders as $template => $blockNames) {
            $blocksByCategory = [];
            foreach ($blockNames as $blockName => $order) {
                $blocksByCategory[$allBlockData[$blockName]->blockData['category_id']][$blockName] = $order;
            }
            foreach ($blocksByCategory as $categoryId => $blocks) {
                $fillFrom = 0;
                $fillTo = null;
                $fillBlockNames = [];
                foreach ($blocks as $blockName => $order) {
                    if (!array_key_exists($blockName, $orders)) {
                        if (array_key_exists('order', $allBlockData[$blockName]->blockData)) {
                            $orders[$blockName] = $allBlockData[$blockName]->blockData['order'];
                            $fillTo = $orders[$blockName];
                        } else {
                            $fillBlockNames[] = $blockName;
                        }
                    }
                    if (!$fillBlockNames) {
                        $fillFrom = $orders[$blockName];
                    }
                    if (!is_null($fillTo)) {
                        $this->_fillOrders($orders, $fillBlockNames, $fillFrom, $fillTo);
                        $fillFrom = $fillTo;
                        $fillTo = null;
                    }
                }
                if ($fillBlockNames) {
                    $this->_fillOrders($orders, $fillBlockNames, $fillFrom, $fillFrom + 10 * (count($fillBlockNames) + 1));
                }
            }
        }
    }

    /**
     * @param array $orders
     * @param array $fillBlockNames
     * @param int $fillFrom
     * @param int $fillTo
     */
    protected function _fillOrders(&$orders, &$fillBlockNames, $fillFrom, $fillTo)
    {
        if ($numberToFill = count($fillBlockNames)) {
            $inc = $fillTo <= $fillFrom ? 10 : (($fillTo - $fillFrom) / ($numberToFill + 1));
            foreach ($fillBlockNames as $k => $blockName) {
                $orders[$blockName] = (int) round($fillFrom + ($k + 1) * $inc);
                $this->_blocksCollection->getBlock($blockName)
                    ->setBlockData([
                        'order' => $orders[$blockName]
                    ]);
            }
            $fillBlockNames = [];
        }
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
     * @return BlocksCollection
     */
    public function getBlocksCollection()
    {
        return $this->_blocksCollection;
    }

    /**
     * @param array|bool $updateTemplates
     */
    public function save($updateTemplates)
    {
        $allBlockData = $this->_saveBlockData($this->_blocksCollection->getAggregatedBlocks()); // should have block ids after
        $allBlockData = is_array($updateTemplates) ? array_intersect_key($allBlockData, array_filter($updateTemplates)) : ($updateTemplates ? $allBlockData : []);
        $this->_saveBlockTemplates($allBlockData);
        $this->_saveBlockRepeaters($allBlockData);

        Block::preload('', true); // reload pre-loaded blocks

        // run import for select blocks (can only be run after blocks have been saved as it saves a block_id)
        $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport;
        $selectOptionsImport->setTheme($this->_additionalData['theme'])->run();
        $this->_importErrors = array_merge($this->_importErrors, $selectOptionsImport->getErrors());
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
            $this->_blocksCollection->getBlock($blockName, 'db')->setBlockData($block->getAttributes(), true);
        }
        return $this->_blocksCollection->getAggregatedBlocks(); // return regenerated aggregated blocks
    }

    /**
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     */
    protected function _saveBlockTemplates($allBlockData)
    {
        $allTemplates = Template::get()->keyBy('template');
        $themeTemplates = $this->_saveNewThemeTemplates($allTemplates, $allBlockData);
        $themeTemplateIds = $themeTemplates->pluck('id')->toArray();
        $themeTemplateBlocks = ThemeTemplateBlock::whereIn('theme_template_id', $themeTemplateIds)->get()->groupBy('block_id');
        $themeBlocks = ThemeBlock::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('block_id');

        foreach ($allBlockData as $blockName => $importBlock) {
            $newTemplateIds = array_map(function ($template) use ($allTemplates) {
                return $allTemplates[$template]->id;
            }, $importBlock->templates);
            $newThemeTemplateIds = $themeTemplates->whereIn('template_id', $newTemplateIds)->pluck('id')->toArray();
            if ($importBlock->globalData['show_in_global'] || $importBlock->globalData['show_in_pages']) {
                // save as a theme block (& remove template blocks)
                $themeBlock = $themeBlocks->has($importBlock->blockData['id']) ? $themeBlocks[$importBlock->blockData['id']] : new ThemeBlock;
                if ($newTemplateIds) {
                    $themeBlock->theme_id = $this->_additionalData['theme']->id;
                    $themeBlock->block_id = $importBlock->blockData['id'];
                    $themeBlock->exclude_templates = implode(',', array_diff($themeTemplates->pluck('template_id')->toArray(), $newTemplateIds));
                    $themeBlock->show_in_global = $importBlock->globalData['show_in_global'];
                    $themeBlock->show_in_pages = $importBlock->globalData['show_in_pages'];
                    $themeBlock->save();
                } else {
                    $themeBlock->delete();
                }
                ThemeTemplateBlock::where('block_id', '=', $importBlock->blockData['id'])->whereIn('theme_template_id', $themeTemplateIds)->delete();
            } else {
                // save a template blocks (& remove theme block)
                $existingThemeTemplateIds = $themeTemplateBlocks->has($importBlock->blockData['id']) ? $themeTemplateBlocks[$importBlock->blockData['id']]->pluck('theme_template_id')->toArray() : [];
                $addThemeTemplateIds = array_diff($newThemeTemplateIds, $existingThemeTemplateIds);
                foreach ($addThemeTemplateIds as $addThemeTemplateId) {
                    $newTemplateBlock = new ThemeTemplateBlock;
                    $newTemplateBlock->block_id = $importBlock->blockData['id'];
                    $newTemplateBlock->theme_template_id = $addThemeTemplateId;
                    $newTemplateBlock->save();
                }
                $deleteThemeTemplateIds = array_diff($existingThemeTemplateIds, $newThemeTemplateIds);
                ThemeTemplateBlock::where('block_id', '=', $importBlock->blockData['id'])->whereIn('theme_template_id', $deleteThemeTemplateIds)->delete();
                ThemeBlock::where('block_id', '=', $importBlock->blockData['id'])->where('theme_id', '=', $this->_additionalData['theme']->id)->delete();
            }
            $this->_blocksCollection->getBlock($blockName, 'db')->setGlobalData($importBlock->globalData, true);
            $this->_blocksCollection->getBlock($blockName, 'db')->templates = $importBlock->templates;
        }

        $this->_deleteUnusedThemeTemplates($allTemplates, $themeTemplates);
    }

    /**
     * @param Collection $allTemplates
     * @param \CoasterCms\Helpers\Admin\Import\Block[] $allBlockData
     * @return Collection
     */
    protected function _saveNewThemeTemplates($allTemplates, $allBlockData)
    {
        $themeTemplates = ThemeTemplate::where('theme_id', '=', $this->_additionalData['theme']->id)->get()->keyBy('template_id');
        foreach ($allBlockData as $blockName => $importBlock) {
            foreach ($importBlock->templates as $template) {
                if (!$allTemplates->has($template)) {
                    $newTemplate = new Template;
                    $newTemplate->template = $template;
                    $newTemplate->label = ucwords(str_replace('_', ' ', $template)) . ' Template';
                    $newTemplate->child_template = 0;
                    $newTemplate->hidden = 0;
                    $newTemplate->save();
                    $allTemplates[$newTemplate->template] = $newTemplate;
                }
                if (!$themeTemplates->has($allTemplates[$template]->id)) {
                    $newThemeTemplate = new ThemeTemplate;
                    $newThemeTemplate->theme_id = $this->_additionalData['theme']->id;
                    $newThemeTemplate->template_id = $allTemplates[$template]->id;
                    $newThemeTemplate->save();
                    $themeTemplates[$newThemeTemplate->template_id] = $newThemeTemplate;
                }
            }
        }
        return $themeTemplates;
    }

    /**
     * @param Collection $allTemplates
     * @param Collection $themeTemplates
     */
    protected function _deleteUnusedThemeTemplates($allTemplates, $themeTemplates)
    {
        $existingTemplateIds = $themeTemplates->pluck('template_id')->toArray();
        $foundTemplateIds = array_map(function ($template) use ($allTemplates) {
            return $allTemplates[$template]->id;
        }, $this->_blocksCollection->getTemplates());
        if ($deleteTemplateIds = array_diff($existingTemplateIds, $foundTemplateIds)) {
            ThemeTemplate::where('theme_id', '=', $this->_additionalData['theme']->id)->whereIn('template_id', $deleteTemplateIds)->delete();
        }
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
        if ($this->_importData) {
            $scopes = $this->_blocksCollection->getScopes();
            $csvOnlyData = $this->_blocksCollection->getBlocksDiffScopes([], array_diff(array_keys($scopes), ['csv']), false);
            $exportBlocks = new BlocksExport($this->_importFile, false);
            $exportBlocks->setTheme($this->_additionalData['theme'])->setExportData($csvOnlyData)->run();
        }
        Directory::remove(pathinfo($this->_importFile, PATHINFO_DIRNAME) . '/blocks');
    }

    /**
     * @return \CoasterCms\Helpers\Admin\Import\Block[]
     */
    public function getExportCollection()
    {
        $this->_loadDbData();
        $this->_loadDbRepeaterData();
        return $this->_blocksCollection->getBlocks('db');
    }

}