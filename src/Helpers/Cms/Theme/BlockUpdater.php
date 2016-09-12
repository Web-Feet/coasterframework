<?php namespace CoasterCms\Helpers\Cms\Theme;

use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder\ThemeBuilderInstance;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Template;
use CoasterCms\Models\TemplateBlock;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeBlock;
use View;

class BlockUpdater
{
    // theme files block options/types
    private static $_selectBlocks;
    private static $_formRules;
    private static $_repeaterBlocks;

    // file overwrite details for blocks
    private static $_blockSettings;

    // system file templates
    private static $_coreTemplates;

    // all blocks in theme files
    private static $_fileBlocks;
    private static $_fileGlobalBlocks;
    private static $_fileTemplateBlocks;
    private static $_fileBlockTemplates;
    private static $_fileCoreBlockTemplates;

    // all blocks/templates used in selected theme
    private static $_databaseBlocks;
    private static $_databaseGlobalBlocks;
    private static $_databaseTemplateBlocks;
    private static $_databaseRepeaterBlocks;
    private static $_databaseTemplates;
    private static $_databaseTemplateIds;
    private static $_databaseBlockTemplates;

    // all blocks in blocks table
    private static $_allBlocks;

    // load block category ids for use in category guess
    private static $_blockCategories;
    private static $_guessedCategoryIds;

    public static function updateTheme(Theme $theme, $blocks = [])
    {
        // process theme templates
        self::processFiles($theme);

        if (empty($blocks)) {
            // by default get all unmodified file blocks data
            $blocks = self::getFileBlocksData();
            // and run all template updates
            foreach ($blocks as $block => $details) {
                if (!empty(self::$_fileBlockTemplates[$block])) {
                    $blocks[$block]['run_template_update'] = 1;
                }
            }
        }

        // update Blocks Table
        foreach ($blocks as $block => $details) {
            // update Blocks
            self::saveBlock($block, $details);

            // update ThemeBlocks/TemplateBlocks
            if (!empty($details['run_template_update'])) {
                self::updateBlockTemplates($theme->id, $block, $details);
            }
        }

        // update form block rules table
        self::updateFormRules();

        // update block repeaters table
        self::updateBlockRepeaters();

        self::_cleanUpOverwriteBlocks($theme);
    }

    private static function _cleanUpOverwriteBlocks(Theme $theme)
    {
        // remove all details except for templates for which the BlockUpdater can't work out or guess

        // re process theme files without the overwrite file
        $blockSettings = self::$_blockSettings;
        self::processFiles($theme, false);

        // check for extra templates in overwrite file that BlockUpdater did not pick up
        $extraTemplates = [];
        foreach ($blockSettings as $block => $setting) {
            if (!empty($setting['templates'])) {
                $blockFoundInTemplates = empty(self::$_fileBlockTemplates[$block])?[]:self::$_fileBlockTemplates[$block];
                if ($extraBlockTemplates = array_diff(explode(',', $setting['templates']), $blockFoundInTemplates)) {
                    $extraTemplates[$block] = $extraBlockTemplates;
                }
            }
        }

        if (!empty($extraTemplates)) {
            $blocksCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/import/blocks.csv', 'w');
            fputcsv($blocksCsv, [
                'Block Name',
                'Block Label',
                'Block Note',
                'Block Category',
                'Block Type',
                'Global (show in site-wide)',
                'Global (show in pages)',
                'Templates',
                'Block Order'
            ]);
            foreach ($extraTemplates as $block => $templates) {
                fputcsv($blocksCsv, [
                    $block,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    implode(',', $templates),
                    ''
                ]);
            }
            fclose($blocksCsv);
        }
    }

    public static function processFiles(Theme $theme, $overwriteFile = true)
    {
        if (!empty($theme)) {

            $themePath = base_path('resources/views/themes/' . $theme->theme . '/templates');

            PageBuilder::setClass(ThemeBuilderInstance::class, [$overwriteFile], PageLoaderDummy::class, [$theme->theme]);

            if (is_dir($themePath)) {

                foreach (scandir($themePath) as $templateFile) {
                    if (($templateName = explode('.', $templateFile)[0]) && !is_dir($themePath.'/'.$templateFile)) {
                        PageBuilder::setData('template', $templateName);
                        View::make('themes.' . $theme->theme . '.templates.' . $templateName)->render();
                    }
                }

                if ($errors = PageBuilder::getData('errors')) {
                    echo 'Could not complete, errors found in theme:'; dd($errors);
                }

                self::$_selectBlocks = PageBuilder::getData('selectBlocks');
                self::$_formRules = PageBuilder::getData('formRules');
                self::$_repeaterBlocks = PageBuilder::getData('repeaterBlocks');
                self::$_blockSettings = PageBuilder::getData('blockSettings');
                self::$_fileTemplateBlocks = PageBuilder::getData('templateBlocks');
                self::$_coreTemplates = PageBuilder::getData('coreTemplates');

                self::_processDatabaseBlocks($theme->id);
                self::_processFileBlocks();
            }
        }

        if (empty(self::$_fileBlocks)) {
            throw new \Exception('no blocks found, theme or templates may not exist');
        }
    }

    public static function exportBlocks($theme)
    {
        // convert db data to blocks override file

        if (!empty($theme)) {

            self::_processDatabaseBlocks($theme->id);

            @mkdir(base_path().'/resources/views/themes/'.$theme->theme.'/export');
            @mkdir(base_path().'/resources/views/themes/'.$theme->theme.'/export/blocks');
            $blocksCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/blocks.csv', 'w');
            $blockCategoriesCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/blocks/categories.csv', 'w');
            $selectOptionsCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/blocks/select_options.csv', 'w');
            $formRulesCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/blocks/form_rules.csv', 'w');

            fputcsv($selectOptionsCsv, [
                'Block Name',
                'Option',
                'Value'
            ]);

            $blockSelectOptions = [];
            $selectOptions = BlockSelectOption::all();
            if (!$selectOptions->isEmpty()) {
                foreach ($selectOptions as $selectOption) {
                    if (!isset($blockSelectOptions[$selectOption->block_id])) {
                        $blockSelectOptions[$selectOption->block_id] = [];
                    }
                    $blockSelectOptions[$selectOption->block_id][] = [
                        $selectOption->option,
                        $selectOption->value
                    ];
                }
            }

            fputcsv($formRulesCsv, [
                'Form Template',
                'Field',
                'Rule'
            ]);

            $formRules = BlockFormRule::all();
            if (!$formRules->isEmpty()) {
                $formsDir = base_path().'/resources/views/themes/'.$theme->theme.'/blocks/forms';
                if (is_dir($formsDir)) {
                    $themeForms = [];
                    foreach (scandir($formsDir) as $formTemplate) {
                        if (!in_array($formTemplate, ['.', '..'])) {
                            $formTemplateParts = explode('.', $formTemplate);
                            $themeForms[] = $formTemplateParts[0];
                        }
                    }
                    foreach ($formRules as $formRule) {
                        if (in_array($formRule->form_template, $themeForms)) {
                            fputcsv($formRulesCsv, [
                                $formRule->form_template,
                                $formRule->field,
                                $formRule->rule
                            ]);
                        }
                    }
                }
            }

            fputcsv($blocksCsv, [
                'Block Name',
                'Block Label',
                'Block Note',
                'Block Category',
                'Block Type',
                'Global (show in site-wide)',
                'Global (show in pages)',
                'Templates',
                'Block Order'
            ]);

            $categoryIds = [];
            foreach (self::$_databaseBlocks as $blockName => $block) {
                $categoryIds[$block->category_id] = $block->category_id;
                fputcsv($blocksCsv, [
                    $blockName,
                    self::$_databaseBlocks[$blockName]->label,
                    self::$_databaseBlocks[$blockName]->note,
                    self::_getBlockCategory($block->category_id, 'name'),
                    self::$_databaseBlocks[$blockName]->type,
                    isset(self::$_databaseGlobalBlocks[$blockName])&&self::$_databaseGlobalBlocks[$blockName]->show_in_global?'yes':'no',
                    isset(self::$_databaseGlobalBlocks[$blockName])&&self::$_databaseGlobalBlocks[$blockName]->show_in_pages?'yes':'no',
                    isset(self::$_databaseBlockTemplates[$blockName])?implode(',', self::$_databaseBlockTemplates[$blockName]):'',
                    self::$_databaseBlocks[$blockName]->order
                ]);
                if (!empty($blockSelectOptions[$block->id])) {
                    foreach ($blockSelectOptions[$block->id] as $blockSelectOption) {
                        fputcsv($selectOptionsCsv, [
                            $block->name,
                            $blockSelectOption[0],
                            $blockSelectOption[1]
                        ]);
                    }
                }
            }

            fputcsv($blockCategoriesCsv, [
                'Block Category',
                'Category Order'
            ]);

            foreach ($categoryIds as $categoryId) {
                if ($category = self::_getBlockCategory($categoryId)) {
                    fputcsv($blockCategoriesCsv, [
                        $category->name,
                        $category->order
                    ]);
                }
            }

            fclose($blocksCsv);
            fclose($blockCategoriesCsv);
            fclose($selectOptionsCsv);
            fclose($formRulesCsv);

        }
    }

    public static function updateFormRules()
    {
        if (!empty(self::$_formRules)) {
            BlockFormRule::import(self::$_formRules);
        }
    }

    private static function _getBlockCategory($categoryId, $field = null)
    {
        if (!isset(self::$_blockCategories)) {
            foreach (BlockCategory::all() as $category) {
                self::$_blockCategories[$category->id] = $category;
            }
        }

        if (isset(self::$_blockCategories[$categoryId])) {
            if ($field) {
                if (isset(self::$_blockCategories[$categoryId]->{$field})) {
                    return self::$_blockCategories[$categoryId]->{$field};
                }
            } else {
                return self::$_blockCategories[$categoryId];
            }
        }
        return null;
    }

    private static function _processFileBlocks()
    {
        self::$_fileBlocks = [];
        self::$_fileGlobalBlocks = [];

        $blockCounter = [];
        $templateCount = 0;
        foreach (self::$_fileTemplateBlocks as $template => $blocks) {
            if (strpos($template, '__core_') !== 0) {
                $templateCount++;
            } else {
                // move non template specific blocks (repeater blocks) to end of array
                unset(self::$_fileTemplateBlocks[$template]);
                self::$_fileTemplateBlocks[$template] = $blocks;
            }
        }

        $blockOrders = [];
        foreach (self::$_allBlocks as $block => $details) {
            $blockOrders[$block] = $details->order;
        }

        // force template adds from overwrite file
        if (!empty(self::$_blockSettings)) {
            foreach (self::$_blockSettings as $block => $fields) {
                if (!empty($fields['templates'])) {
                    if ($fields['templates'] == '*') {
                        foreach (self::$_fileTemplateBlocks as $template => $blocks) {
                            if (!in_array($block, self::$_fileTemplateBlocks[$template]) && strpos($template, '__core_') !== 0) {
                                self::$_fileTemplateBlocks[$template][] = $block;
                                PageBuilder::setData('template', $template);
                                PageBuilder::block($block);
                            }
                        }
                    } else {
                        $templates = explode(',', $fields['templates']);
                        if (!empty($templates)) {
                            foreach ($templates as $template) {
                                if (isset(self::$_fileTemplateBlocks[$template])) {
                                    if (!in_array($block, self::$_fileTemplateBlocks[$template])) {
                                        self::$_fileTemplateBlocks[$template][] = $block;
                                        PageBuilder::setData('template', $template);
                                        PageBuilder::block($block);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // create fileBlockTemplates array
        self::$_fileBlockTemplates = [];
        self::$_fileCoreBlockTemplates = [];
        foreach (self::$_fileTemplateBlocks as $template => $blocks) {
            if (strpos($template, '__core_') !== 0) {
                foreach ($blocks as $block) {
                    if (!isset(self::$_fileBlockTemplates[$block])) {
                        self::$_fileBlockTemplates[$block] = [];
                    }
                    self::$_fileBlockTemplates[$block][] = $template;
                }
            }
            foreach ($blocks as $block) {
                if (!isset(self::$_fileCoreBlockTemplates[$block])) {
                    self::$_fileCoreBlockTemplates[$block] = [];
                }
                self::$_fileCoreBlockTemplates[$block][] = $template;
            }
        }

        // get full block list and details in one array
        foreach (self::$_fileTemplateBlocks as $template => $blocks) {
            $order = 10;
            foreach ($blocks as $k => $block) {
                if (!isset(self::$_fileBlocks[$block])) {
                    if (!empty(self::$_blockSettings[$block]['order'])) {
                        $blockOrders[$block] = self::$_blockSettings[$block]['order'];
                    } else {
                        if (isset($blocks[$k - 1]) && !empty($blockOrders[$blocks[$k - 1]])) {
                            $order = $blockOrders[$blocks[$k - 1]] + 10;
                        } else {
                            $order += 10;
                        }
                        $blockOrders[$block] = $order;
                    }
                    $blockCounter[$block] = 0;
                    self::$_fileBlocks[$block] = ['order' => $blockOrders[$block]];
                    self::$_fileBlocks[$block] = ['order' => $blockOrders[$block]];
                    if (in_array($block, self::$_repeaterBlocks)) {
                        self::$_fileBlocks[$block]['type'] = 'repeater';
                    }
                }
                if (strpos($template, '__core_') !== 0) {
                    $blockCounter[$block]++;
                }
            }
        }

        foreach ($blockCounter as $block => $count) {
            if (($count / $templateCount) >= 0.7) {
                self::$_fileGlobalBlocks[$block] = 1;
            }
        }

    }

    private static function _processDatabaseBlocks($themeId)
    {
        self::$_allBlocks = [];
        self::$_databaseBlocks = [];
        self::$_databaseGlobalBlocks = [];
        self::$_databaseTemplateBlocks = [];
        self::$_databaseTemplates = [];
        self::$_databaseTemplateIds = [];
        self::$_databaseBlockTemplates = [];

        $blocksById = [];
        $allBlocks = Block::all();
        foreach ($allBlocks as $block) {
            $blocksById[$block->id] = $block;
            self::$_allBlocks[$block->name] = $block;
        }

        $templates = Template::where('theme_id', '=', $themeId)->get();
        if (!$templates->isEmpty()) {
            foreach ($templates as $template) {
                self::$_databaseTemplates[$template->template] = $template;
                self::$_databaseTemplateIds[$template->id] = $template;
            }
        }
        self::saveTemplates($themeId); // save any new templates
        if (!empty(self::$_databaseTemplates)) {
            foreach (self::$_databaseTemplates as $template) {
                self::$_databaseTemplateBlocks[$template->template] = [];
            }
            $templateBlocks = TemplateBlock::whereIn('template_id', array_keys(self::$_databaseTemplateIds))->get();
            if (!$templateBlocks->isEmpty()) {
                foreach ($templateBlocks as $templateBlock) {
                    if (!isset($blocksById[$templateBlock->block_id])) {
                        $templateBlock->delete();
                    } else {
                        self::$_databaseBlocks[$blocksById[$templateBlock->block_id]->name] = $blocksById[$templateBlock->block_id];
                        self::$_databaseTemplateBlocks[self::$_databaseTemplateIds[$templateBlock->template_id]->template][$templateBlock->block_id] = $blocksById[$templateBlock->block_id]->name;
                        if (!isset(self::$_databaseBlockTemplates[$blocksById[$templateBlock->block_id]->name])) {
                            self::$_databaseBlockTemplates[$blocksById[$templateBlock->block_id]->name] = [];
                        }
                        if (!in_array(self::$_databaseTemplateIds[$templateBlock->template_id]->template, self::$_databaseBlockTemplates[$blocksById[$templateBlock->block_id]->name])) {
                            self::$_databaseBlockTemplates[$blocksById[$templateBlock->block_id]->name][] = self::$_databaseTemplateIds[$templateBlock->template_id]->template;
                        }
                    }
                }
            }
        }

        $globalBlocks = ThemeBlock::where('theme_id', '=', $themeId)->get();
        foreach ($globalBlocks as $globalBlock) {
            if (!isset($blocksById[$globalBlock->block_id])) {
                $globalBlock->delete();
            } else {
                self::$_databaseBlocks[$blocksById[$globalBlock->block_id]->name] = $blocksById[$globalBlock->block_id];
                self::$_databaseGlobalBlocks[$blocksById[$globalBlock->block_id]->name] = $globalBlock;
                if (!isset(self::$_databaseBlockTemplates[$blocksById[$globalBlock->block_id]->name])) {
                    self::$_databaseBlockTemplates[$blocksById[$globalBlock->block_id]->name] = [];
                }
                $excludedTemplates = !empty($globalBlock->exclude_templates)?explode(',', $globalBlock->exclude_templates):[];
                foreach (self::$_databaseTemplateIds as $templateId => $template) {
                    if (!in_array($templateId, $excludedTemplates) && !in_array($template->template, self::$_databaseBlockTemplates[$blocksById[$globalBlock->block_id]->name])) {
                        self::$_databaseBlockTemplates[$blocksById[$globalBlock->block_id]->name][] = $template->template;
                    }
                }
            }
        }

        // load repeaters
        self::$_databaseRepeaterBlocks = [];
        $repeaterBlocks = BlockRepeater::all();
        foreach ($repeaterBlocks as $repeaterBlock) {
            self::$_databaseRepeaterBlocks[$repeaterBlock->block_id] = $repeaterBlock;
        }

        foreach (self::$_databaseBlocks as $block => $databaseBlock) {
            if ($databaseBlock->type == 'repeater') {
                self::_loadDatabaseRepeaterBlocks($databaseBlock->id, $blocksById);
            }
        }

    }

    private static function _loadDatabaseRepeaterBlocks($repeaterBlockId, $blocksById)
    {
        if (!empty(self::$_databaseRepeaterBlocks[$repeaterBlockId])) {
            $blockIds = explode(',', self::$_databaseRepeaterBlocks[$repeaterBlockId]->blocks);
            foreach ($blockIds as $blockId) {
                if (isset($blocksById[$blockId]) && !isset(self::$_databaseBlocks[$blocksById[$blockId]->name])) {
                    self::$_databaseBlocks[$blocksById[$blockId]->name] = $blocksById[$blockId];
                    if ($blocksById[$blockId]->type == 'repeater') {
                        self::_loadDatabaseRepeaterBlocks($blockId, $blocksById);
                    }
                }
            }
        }
    }

    /*
     * Get Data Functions
     */

    public static function getNewBlocks()
    {
        // array of empty details array
        return array_diff_key(self::$_fileBlocks, self::$_databaseBlocks);
    }

    public static function getExistingBlocks()
    {
        // array of Block Models
        return array_intersect_key(self::$_databaseBlocks, self::$_fileBlocks);
    }

    public static function getDeletedBlocks()
    {
        // array of Block Models
        return array_diff_key(self::$_databaseBlocks, self::$_fileBlocks);
    }

    public static function getFileBlocksData()
    {
        // array of all block data for the current theme (with full block details)
        $fileBlocksData = [];
        foreach (self::$_fileBlocks as $fileBlock => $details) {
            $fileBlocksData[$fileBlock] = self::_getBlockData($fileBlock, $details);
        }
        return $fileBlocksData;
    }

    public static function getDatabaseBlocks($themeId)
    {
        if (empty(self::$_databaseBlocks)) {
            self::_processDatabaseBlocks($themeId);
        }
        return self::$_databaseBlocks;
    }

    public static function getTemplateList()
    {
        $templates = array_keys(self::$_fileTemplateBlocks);
        foreach ($templates as $i => $template) {
            if (strpos($template, '__core_') === 0) {
                unset($templates[$i]);
            }
        }
        return implode(', ', $templates);
    }

    private static function _printTemplates($block)  {
        $templates = [];
        if (empty(self::$_fileBlockTemplates[$block] )) {
            return '';
        }
        if (!empty(self::$_blockSettings[$block]['templates'])) {
            if (self::$_blockSettings[$block]['templates'] == '*') {
                foreach (self::$_fileBlockTemplates[$block] as $template) {
                    $templates[] = '<span class="text-danger">'.$template.'</span>';
                }
            } else {
                $overwriteTemplates = explode(',', self::$_blockSettings[$block]['templates']);
                foreach (self::$_fileBlockTemplates[$block] as $template) {
                    if (in_array($template, $overwriteTemplates)) {
                        $templates[] = '<span class="text-danger">'.$template.'</span>';
                    } else {
                        $templates[] = '<span class="text-success">'.$template.'</span>';
                    }
                }
            }
        } else {
            foreach (self::$_fileBlockTemplates[$block] as $template) {
                $templates[] = '<span class="text-success">'.$template.'</span>';
            }
        }
        return implode(', ', $templates);
    }

    public static function getMainTableData()
    {
        $themeBlocks = [];
        // if new blocks
        foreach (self::getNewBlocks() as $newBlock => $details) {
            $themeBlocks[$newBlock] = self::_getBlockData($newBlock, $details);
            $themeBlocks[$newBlock]['run_template_update'] = 1;
            $themeBlocks[$newBlock]['rowClass'] = 1; // new block found in templates
            $themeBlocks[$newBlock]['updates'] = 'new block found, will add on update';
            if (empty(self::$_fileBlockTemplates[$newBlock])) {
                $themeBlocks[$newBlock]['run_template_update'] = -1;
                $themeBlocks[$newBlock]['templates'] = '';
            } else {
                $themeBlocks[$newBlock]['templates'] = self::_printTemplates($newBlock);
            }
            self::_coreTemplateCheck($themeBlocks, $newBlock);
        }

        foreach (self::getExistingBlocks() as $existingBlock => $details) {
            $themeBlocks[$existingBlock] = self::_getBlockData($existingBlock);
            $themeBlocks[$existingBlock]['updates'] = '';
            $databaseTemplates = !empty(self::$_databaseBlockTemplates[$existingBlock])?self::$_databaseBlockTemplates[$existingBlock]:[];
            $fileTemplates = !empty(self::$_fileBlockTemplates[$existingBlock])?self::$_fileBlockTemplates[$existingBlock]:[];
            if (empty($databaseTemplates) && empty($fileTemplates)) {
                $themeBlocks[$existingBlock]['rowClass'] = 5; // unchanged (repeater block)
                $themeBlocks[$existingBlock]['templates'] = '';
                $themeBlocks[$existingBlock]['run_template_update'] = -1;
            } elseif (empty($databaseTemplates) || empty($fileTemplates) || array_diff($databaseTemplates, $fileTemplates) || array_diff($fileTemplates, $databaseTemplates)) {
                $addedTemplates = array_diff($fileTemplates, $databaseTemplates);
                $removedTemplates = array_diff($databaseTemplates, $fileTemplates);
                $themeBlocks[$existingBlock]['run_template_update'] = 1;
                $themeBlocks[$existingBlock]['rowClass'] = 3; // changed templates
                $themeBlocks[$existingBlock]['templates'] = self::_printTemplates($existingBlock);
                if (!empty($addedTemplates)) {
                    $themeBlocks[$existingBlock]['updates'] .= 'block added to the '.implode(' & ', $addedTemplates) . ' ' . str_plural('template', count($addedTemplates)) . ', ';
                }
                if (!empty($removedTemplates)) {
                    $themeBlocks[$existingBlock]['updates'] .= 'block removed from the '.implode(' & ', $removedTemplates) . ' ' . str_plural('template', count($removedTemplates)) . ', ';
                }
                $themeBlocks[$existingBlock]['updates'] .= 'template update required';
            } else {
                $themeBlocks[$existingBlock]['run_template_update'] = 0;
                $themeBlocks[$existingBlock]['rowClass'] = 5; // unchanged
                $themeBlocks[$existingBlock]['templates'] = self::_printTemplates($existingBlock);
            }
            self::_coreTemplateCheck($themeBlocks, $existingBlock);
        }

        foreach (self::getDeletedBlocks() as $deletedBlock => $details) {
            $themeBlocks[$deletedBlock] = self::_getBlockData($deletedBlock);
            $themeBlocks[$deletedBlock]['updates'] = '';
            $databaseTemplates = !empty(self::$_databaseBlockTemplates[$deletedBlock])?self::$_databaseBlockTemplates[$deletedBlock]:[];
            if (!empty($databaseTemplates)) {
                $themeBlocks[$deletedBlock]['updates'] .= 'block removed from the '.implode(' & ', $databaseTemplates) . ' ' . str_plural('template', count($databaseTemplates)) . ', ';
                $themeBlocks[$deletedBlock]['run_template_update'] = 1;
            } else {
                $themeBlocks[$deletedBlock]['run_template_update'] = -1;
            }
            $themeBlocks[$deletedBlock]['updates'] .= 'block no longer used and will be removed from theme on update';
            $themeBlocks[$deletedBlock]['rowClass'] = 2; // block in no longer found templates
            $themeBlocks[$deletedBlock]['templates'] = 'none';
        }

        // check repeater changes
        foreach (self::getExistingBlocks() as $existingBlock => $details) {
            if (!empty(self::$_repeaterBlocks[$existingBlock])) {
                $changed = false;
                $arrayList = [];
                foreach (self::$_repeaterBlocks[$existingBlock] as $repeaterBlock) {
                    if (!isset(self::$_allBlocks[$repeaterBlock])) {
                        $changed = true;
                        break;
                    }
                    $arrayList[] = self::$_allBlocks[$repeaterBlock]->id;
                }
                if (!$changed) {
                    $implodedList = implode(',', $arrayList);
                    if (empty(self::$_databaseRepeaterBlocks[self::$_allBlocks[$existingBlock]->id]) || self::$_databaseRepeaterBlocks[self::$_allBlocks[$existingBlock]->id]->blocks != $implodedList) {
                        $changed = true;
                    }
                }
                if ($changed) {
                    $themeBlocks[$existingBlock]['rowClass'] = 3;
                    $themeBlocks[$existingBlock]['updates'] = 'blocks added or removed from the repeater template, will save changes on update';
                }
            }
        }

        uasort($themeBlocks, function($blockA, $blockB) {
            if ($blockA['rowClass'] == $blockB['rowClass']) {
                if ($blockA['run_template_update'] == $blockB['run_template_update']) {
                    if ($blockA['name'] == $blockB['name']) {
                        return 0;
                    }
                    return ($blockA['name'] > $blockB['name']) ? +1 : -1;
                }
                return ($blockA['run_template_update'] < $blockB['run_template_update']) ? +1 : -1;
            }
            return ($blockA['rowClass'] > $blockB['rowClass']) ? +1 : -1;
        });

        return $themeBlocks;
    }

    private static function _coreTemplateCheck(&$themeBlocks, $blockName)
    {
        $coreFound = false;
        $repeaterFound = false;
        $coreTemplates = self::$_fileCoreBlockTemplates[$blockName];
        if (in_array('__core_repeater', $coreTemplates)) {
            if (empty($themeBlocks[$blockName]['templates']) && count($coreTemplates) == 1) {
                $themeBlocks[$blockName]['templates'] = 'block only found inside repeaters, no template updates required';
            }
            $repeaterFound = true;
        }
        if (in_array('__core_category', $coreTemplates)) {
            $themeBlocks[$blockName]['templates'] .= ', block also found in a category template';
            if ($themeBlocks[$blockName]['rowClass'] == 5) {
                $themeBlocks[$blockName]['rowClass'] = 4; // info, there may be changes in blocks in the category templates
            }
            $coreFound = true;
        }
        if (in_array('__core_otherPage', $coreTemplates)) {
            $themeBlocks[$blockName]['templates'] .= ', block also called with a custom page_id';
            if ($themeBlocks[$blockName]['rowClass'] == 5) {
                $themeBlocks[$blockName]['rowClass'] = 4; // info, there may be changes in to the template used by the page with page_id
            }
            $coreFound = true;
        }
        if (!$coreFound && !$repeaterFound) {
            $coreTemplateFound = false;
            foreach (self::$_coreTemplates as $coreTemplate) {
                if (in_array($coreTemplate, $coreTemplates)) {
                    $coreTemplateFound = true;
                    break;
                }
            }
            if ($coreTemplateFound) {
                if (empty($themeBlocks[$blockName]['templates'])) {
                    $themeBlocks[$blockName]['templates'] = 'can\'t automatically determine which page templates';
                } else {
                    $themeBlocks[$blockName]['templates'] .= ', can\'t automatically determine all page templates';
                }
                if ($themeBlocks[$blockName]['rowClass'] == 5) {
                    $themeBlocks[$blockName]['rowClass'] = 4; // info, there may be changes to some core templates
                }
            }
        } elseif ($coreFound) {
            $themeBlocks[$blockName]['templates'] .= ' so can\'t automatically determine all page templates';
        }
    }

    private static function _getBlockData($block, $details = [])
    {
        $processedData = [];
        if (isset(self::$_allBlocks[$block])) {
            $processedData['category_id'] = self::$_allBlocks[$block]->category_id;
            $processedData['label'] = self::$_allBlocks[$block]->label;
            $processedData['name'] = self::$_allBlocks[$block]->name;
            $processedData['type'] = self::$_allBlocks[$block]->type;
        } else {
            $processedData['label'] = ucwords(str_replace('_', ' ', $block));
            $processedData['name'] = $block;
            $processedData['type'] = !empty($details['type']) ? $details['type'] : self::typeGuess($block);
            $processedData['category_id'] = self::_categoryGuess($block, $processedData['type']);
            $processedData['note'] = !empty($details['note']) ? $details['note'] : '';
        }
        if (!empty(self::$_databaseBlocks[$block])) {
            $processedData['global_site'] = (bool)(!empty(self::$_databaseGlobalBlocks[$block]) ? self::$_databaseGlobalBlocks[$block]->show_in_global : 0);
            $processedData['global_pages'] = (bool)(!empty(self::$_databaseGlobalBlocks[$block]) ? self::$_databaseGlobalBlocks[$block]->show_in_pages : 0);
        } else {
            $processedData['global_site'] = isset(self::$_fileGlobalBlocks[$block]);
            $processedData['global_pages'] = (bool)stristr($block, 'meta');
        }
        // use overwrite data if found
        if (!empty(self::$_blockSettings[$block])) {
            foreach (self::$_blockSettings[$block] as $setting => $value) {
                $processedData[$setting] = $value;
            }
        }

        return $processedData;
    }

    private static function _categoryGuess($block, $type = '')
    {
        if (!isset(self::$_guessedCategoryIds)) {
            $findKeys = [
                'main' => ['main'],
                'banner' => ['banner', 'carousel'],
                'seo' => ['seo'],
                'footer' => ['foot'],
                'header' => ['head']
            ];

            self::$_guessedCategoryIds = [];
            $first = true;
            foreach (BlockCategory::all() as $category) {
                if ($first) {
                    $first = false;
                    $keys['main'] = $category->id;
                }
                foreach ($findKeys as $key => $matches) {
                    foreach ($matches as $match) {
                        if (stristr($category->name, $match)) {
                            self::$_guessedCategoryIds[$key] = $category->id;
                        }
                    }
                }
            }
        } else {
            $findKeys = [];
        }

        if ($type == 'repeater') {
            $findKeys[str_plural(str_replace('_', ' ', $block))] = [$block];
        }

        if (!empty($findKeys)) {
            $order = 0;
            foreach ($findKeys as $key => $matches) {
                $order += 10;
                if (!isset(self::$_guessedCategoryIds[$key])) {
                    $newBlockCategory = new BlockCategory;
                    $newBlockCategory->name = ucwords($key);
                    $newBlockCategory->order = ($key=='seo')?100:$order;
                    $newBlockCategory->save();
                    self::$_guessedCategoryIds[$key] = $newBlockCategory->id;
                }
            }
        }

        $categoryFound = self::$_guessedCategoryIds['main'];

        $categoriesArr = [];
        $categoriesArr[self::$_guessedCategoryIds['seo']] = ['meta'];
        $categoriesArr[self::$_guessedCategoryIds['header']] = ['header_html', 'head', 'logo', 'phone'];
        $categoriesArr[self::$_guessedCategoryIds['footer']] = ['footer_html', 'foot', 'address', 'email', 'copyright'];
        $categoriesArr[self::$_guessedCategoryIds['banner']] = ['banner', 'carousel'];

        foreach ($categoriesArr as $_guessedCategoryIds => $matches) {
            foreach ($matches as $match) {
                if (stristr($block, $match)) {
                    $categoryFound = $_guessedCategoryIds;
                }
            }
        }

        return $categoryFound;
    }

    public static function typeGuess($block)
    {
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
        if (strpos($typeFound, 'select') === false && !empty(self::$_selectBlocks[$block])) {
            $typeFound = 'select';
        }
        if (!empty(self::$_repeaterBlocks[$block])) {
            $typeFound = 'repeater';
        }
        return $typeFound;
    }

    /*
     * Update Functions
     */

    public static function saveTemplates($themeId)
    {
        if (!empty(self::$_fileTemplateBlocks)) {
            foreach (self::$_fileTemplateBlocks as $template => $blocks) {
                if (empty(self::$_databaseTemplates[$template]) && strpos($template, '__core_') !== 0) {
                    $newTemplate = new Template;
                    $newTemplate->theme_id = $themeId;
                    $newTemplate->template = $template;
                    $newTemplate->label = ucwords(str_replace('_', ' ', $template)) . ' Template';
                    $newTemplate->save();
                    self::$_databaseTemplates[$template] = $newTemplate;
                    self::$_databaseTemplateIds[$newTemplate->id] = $newTemplate;
                }
            }
        }
    }

    public static function saveBlock($block, $blockData)
    {
        if (isset(self::$_fileBlocks[$block])) {
            $blockData['category_id'] = !empty($blockData['category_id'])?$blockData['category_id']:0;
            if (!empty(self::$_allBlocks[$block])) {
                $existingBlock = self::$_allBlocks[$block];
                if ($existingBlock->type != $blockData['type'] || $existingBlock->label != $blockData['label'] || $existingBlock->category_id != $blockData['category_id']) {
                    $existingBlock->category_id = $blockData['category_id'];
                    $existingBlock->type = $blockData['type'];
                    $existingBlock->label = $blockData['label'];
                    $existingBlock->order = self::$_fileBlocks[$block]['order'];
                    $existingBlock->save();
                }
            } else {
                $newBlock = new Block;
                $newBlock->category_id = $blockData['category_id'];
                $newBlock->name = $block;
                $newBlock->type = $blockData['type'];
                $newBlock->note = !empty($blockData['note'])?$blockData['note']:'';
                $newBlock->label = $blockData['label'];
                $newBlock->order = self::$_fileBlocks[$block]['order'];
                $newBlock->save();
                self::$_allBlocks[$block] = $newBlock;
            }
        }
        if (!empty(self::$_selectBlocks[$block])) {
            BlockSelectOption::import(self::$_allBlocks[$block]->id, self::$_selectBlocks[$block]);
        }
    }

    public static function updateBlockTemplates($themeId, $block, $blockData)
    {
        if (!isset(self::$_fileBlocks[$block])) {
            $blockData['global_pages'] = 0;
            $blockData['global_site'] = 0;
        }

        // do empty check as new blocks won't be found
        $databaseBlockTemplates = !empty(self::$_databaseBlockTemplates[$block]) ? self::$_databaseBlockTemplates[$block] : [];
        $fileBlockTemplates = !empty(self::$_fileBlockTemplates[$block]) ? self::$_fileBlockTemplates[$block] : [];

        if (!empty($blockData['global_pages']) || !empty($blockData['global_site'])) {
            $toAdd = [];
            $toDelete = $databaseBlockTemplates;

            $fileBlockTemplatesIds = [];
            foreach ($fileBlockTemplates as $fileBlockTemplate) {
                $fileBlockTemplatesIds[] = self::$_databaseTemplates[$fileBlockTemplate]->id;
            }

            $excludeTemplates = array_diff(array_keys(self::$_databaseTemplateIds), $fileBlockTemplatesIds);

            sort($excludeTemplates);
            $excludeList = implode(',', $excludeTemplates);
            $blockData['global_pages'] = !empty($blockData['global_pages']) ? 1 : 0;
            $blockData['global_site'] = !empty($blockData['global_site']) ? 1 : 0;

            // Insert or Update ThemeBlock
            if (empty(self::$_databaseGlobalBlocks[$block])) {
                $newThemeBlock = new ThemeBlock;
                $newThemeBlock->theme_id = $themeId;
                $newThemeBlock->block_id = self::$_allBlocks[$block]->id;
                $newThemeBlock->show_in_pages = $blockData['global_pages'];
                $newThemeBlock->show_in_global = $blockData['global_site'];
                $newThemeBlock->exclude_templates = $excludeList;
                $newThemeBlock->save();
            } elseif (
                self::$_databaseGlobalBlocks[$block]->show_in_pages != $blockData['global_pages'] ||
                self::$_databaseGlobalBlocks[$block]->show_in_global != $blockData['global_site'] ||
                self::$_databaseGlobalBlocks[$block]->exclude_templates != $excludeList
            ) {
                self::$_databaseGlobalBlocks[$block]->show_in_pages = $blockData['global_pages'];
                self::$_databaseGlobalBlocks[$block]->show_in_global = $blockData['global_site'];
                self::$_databaseGlobalBlocks[$block]->exclude_templates = $excludeList;
                self::$_databaseGlobalBlocks[$block]->save();
            }

        } else {
            // Delete from theme blocks if no longer a theme block
            if (!empty(self::$_databaseGlobalBlocks[$block])) {
                ThemeBlock::where('block_id', '=', self::$_allBlocks[$block]->id)->where('theme_id', '=', $themeId)->delete();
                $databaseBlockTemplates = [];
            }

            $toAdd = array_diff($fileBlockTemplates, $databaseBlockTemplates);
            $toDelete = array_diff($databaseBlockTemplates, $fileBlockTemplates);
        }

        // Update TemplateBlocks
        if (!empty($toDelete)) {
            $templateIds = [];
            foreach ($toDelete as $template) {
                $templateIds[] = self::$_databaseTemplates[$template]->id;
            }
            TemplateBlock::where('block_id', '=', self::$_allBlocks[$block]->id)->whereIn('template_id', $templateIds)->delete();
        }
        if (!empty($toAdd)) {
            foreach ($toAdd as $template) {
                $newTemplateBlock = new TemplateBlock;
                $newTemplateBlock->block_id = self::$_allBlocks[$block]->id;
                $newTemplateBlock->template_id = self::$_databaseTemplates[$template]->id;
                $newTemplateBlock->save();
            }
        }
    }

    public static function updateBlockRepeaters()
    {
        foreach (self::$_repeaterBlocks as $repeater => $repeaterBlocks) {
            $arrayList = [];
            foreach ($repeaterBlocks as $repeaterBlock) {
                $arrayList[] = self::$_allBlocks[$repeaterBlock]->id;
            }
            $implodedList = implode(',', $arrayList);
            if (empty(self::$_databaseRepeaterBlocks[self::$_allBlocks[$repeater]->id])) {
                $newBlockRepeater = new BlockRepeater;
                $newBlockRepeater->block_id = self::$_allBlocks[$repeater]->id;
                $newBlockRepeater->blocks = $implodedList;
                $newBlockRepeater->save();
            } elseif (self::$_databaseRepeaterBlocks[self::$_allBlocks[$repeater]->id]->blocks != $implodedList) {
                self::$_databaseRepeaterBlocks[self::$_allBlocks[$repeater]->id]->blocks = $implodedList;
                self::$_databaseRepeaterBlocks[self::$_allBlocks[$repeater]->id]->save();
            }
        }
    }

}
