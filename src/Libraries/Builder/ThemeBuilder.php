<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Helpers\BlockManager;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Template;
use CoasterCms\Models\TemplateBlock;
use CoasterCms\Models\Theme;
use CoasterCms\Models\ThemeBlock;
use Illuminate\Support\Facades\View;

class ThemeBuilder
{

    // default properties from PageBuilder class
    public static $theme;
    public static $page_info;
    public static $preview = false;
    public static $page_levels = [];

    // current settings
    private static $_theme;
    private static $_template;
    private static $_repeater;

    // all blocks in blocks table
    private static $_allBlocks;

    // used repeater blocks in theme files
    private static $_repeaterBlocks;
    private static $_repeaterTemplates;

    // block options
    private static $_selectBlocks;
    private static $_formRules;

    // block overwrite details
    private static $_blockSettings;

    // core templates (used if actual template can't be determined)
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

    // load block category ids for use in category guess
    private static $_categoryIds;
    private static $_blockCategories;
    private static $_guessedCategoryIds;
    private static $_csvCategoryData;

    private static $_error;

    public static function updateTheme($themeId, $blocks = [])
    {
        // process theme templates
        self::processFiles($themeId);

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
                self::updateBlockTemplates($block, $details);
            }
        }

        // update form block rules table
        self::updateFormRules();

        // update block repeaters table
        self::updateBlockRepeaters();
    }

    public static function cleanOverwriteFile($themeId)
    {
        // remove all details except for templates for which ThemeBuilder can't work out

        // re process theme files without the overwrite file
        self::$_blockSettings = [];
        self::processFiles($themeId, false);
        $blocksFound = self::$_fileBlockTemplates;

        // check extra for extra templates in overwrite file that ThemeBuilder did not pick up
        $extraTemplates = [];
        self::_getBlockOverwriteFile();
        foreach (self::$_blockSettings as $block => $setting) {
            if (!empty($setting['templates'])) {
                $blocksFound[$block] = empty($blocksFound[$block])?[]:$blocksFound[$block];
                if ($extraBlockTemplates = array_diff(explode(',', $setting['templates']), $blocksFound[$block])) {
                    $extraTemplates[$block] = $extraBlockTemplates;
                }
            }
        }

        if (!empty($extraTemplates)) {
            $blocksCsv = fopen(base_path().'/resources/views/themes/'.self::$_theme->theme.'/import/blocks.csv', 'w');
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

    public static function processFiles($themeId, $overwriteFile = true)
    {
        self::$_theme = Theme::find($themeId);
        if (!empty(self::$_theme)) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\ThemeBuilder');

            $themePath = base_path('resources/views/themes/' . self::$_theme->theme . '/templates');

            self::_checkRepeaterTemplates();
            self::_checkSelectBlocks();
            self::_checkFormRules();

            if ($overwriteFile) {
                self::_getBlockOverwriteFile();
            }

            self::$theme = self::$_theme->theme;
            self::$page_info = new \stdClass;
            self::$page_info->name = '';
            self::$page_info->url = '';
            self::$page_info->page_id = 0;
            self::$page_info->live_version = 0;

            \CoasterCms\Libraries\Builder\PageBuilder::$theme = self::$theme;
            \CoasterCms\Libraries\Builder\PageBuilder::$page_info = self::$page_info;

            if (is_dir($themePath)) {
                self::$_fileTemplateBlocks = [];
                self::$_coreTemplates = ['__core_category', '__core_otherPage', '__core_repeater'];
                foreach (self::$_coreTemplates as $coreTemplate) {
                    self::$_fileTemplateBlocks[$coreTemplate] = [];
                }
                foreach (scandir($themePath) as $templateFile) {
                    if (self::$_template = explode('.', $templateFile)[0]) {
                        self::$page_info->template_name = self::$_template;
                        self::$_fileTemplateBlocks[self::$_template] = [];
                        View::make('themes.' . self::$_theme->theme . '.templates.' . self::$_template)->render();
                        if (self::$_error) {
                            $error = 'Error processing "'.'themes.' . self::$_theme->theme . '.templates.' . self::$_template . '"';
                            throw new \Exception($error . "\r\n" . self::$_error);
                        }
                    }
                }
                self::_processDatabaseBlocks();
                self::_processFileBlocks();
            }

            $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
        }

        if (empty(self::$_fileBlocks)) {
            throw new \Exception('no blocks found, theme or templates may not exist');
        }
    }

    public static function exportBlocks($theme)
    {
        // convert db data to blocks override file

        if (!empty($theme)) {

            self::$_theme = $theme;
            self::_processDatabaseBlocks();

            @mkdir(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export');
            @mkdir(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export/blocks');
            $blocksCsv = fopen(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export/blocks.csv', 'w');
            $blockCategoriesCsv = fopen(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export/blocks/categories.csv', 'w');
            $selectOptionsCsv = fopen(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export/blocks/select_options.csv', 'w');
            $formRulesCsv = fopen(base_path().'/resources/views/themes/'.self::$_theme->theme.'/export/blocks/form_rules.csv', 'w');

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
                $formsDir = base_path().'/resources/views/themes/'.self::$_theme->theme.'/blocks/forms';
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

    private static function _checkRepeaterTemplates()
    {
        self::$_repeater = null;
        self::$_repeaterBlocks = [];
        self::$_repeaterTemplates = [];

        $repeaterPath = base_path('resources/views/themes/' . self::$_theme->theme . '/blocks/repeaters');
        if (is_dir($repeaterPath)) {
            foreach (scandir($repeaterPath) as $repeaterFile) {
                if ($repeaterTemplate = explode('.', $repeaterFile)[0]) {
                    self::$_repeaterTemplates[] = $repeaterTemplate;
                }
            }
        }
    }

    private static function _checkSelectBlocks()
    {
        self::$_selectBlocks = [];

        $selectOptions = base_path('resources/views/themes/' . self::$_theme->theme . '/import/blocks/select_options.csv');
        if (file_exists($selectOptions) && ($fileHandle = fopen($selectOptions, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($fileHandle)) !== false) {
                if ($row++ == 0 && $data[0] == 'Block Name') {
                    continue;
                }
                if (!isset(self::$_selectBlocks[$data[0]])) {
                    self::$_selectBlocks[$data[0]] = [];
                }
                self::$_selectBlocks[$data[0]][$data[2]] = $data[1];
            }
            fclose($fileHandle);
        }
    }

    private static function _checkFormRules()
    {
        self::$_formRules = [];

        $formRules = base_path('resources/views/themes/' . self::$_theme->theme . '/import/blocks/form_rules.csv');
        if (file_exists($formRules) && ($fileHandle = fopen($formRules, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($fileHandle)) !== false) {
                if ($row++ == 0 && $data[0] == 'Form Template') {
                    continue;
                }
                if (!isset(self::$_formRules[$data[0]])) {
                    self::$_formRules[$data[0]] = [];
                }
                self::$_formRules[$data[0]][$data[1]] = $data[2];
            }
            fclose($fileHandle);
        }
    }

    public static function updateFormRules()
    {
        if (!empty(self::$_formRules)) {
            BlockFormRule::import(self::$_formRules);
        }
    }

    private static function _getBlockOverwriteFile()
    {
        self::$_blockSettings = [];

        $selectOptions = base_path('resources/views/themes/' . self::$_theme->theme . '/import/blocks.csv');
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
                                    $setting = self::_getBlockCategoryId($setting);
                                }
                                if ($field == 'name') {
                                    $setting = strtolower($setting);
                                }
                                self::$_blockSettings[$data[0]][$field] = $setting;
                            }
                        }
                    }
                }
            }
            fclose($fileHandle);
        }
    }

    private static function _getBlockCategoryId($categoryName)
    {
        if (!isset(self::$_categoryIds)) {
            foreach (BlockCategory::all() as $category) {
                self::$_categoryIds[trim(strtolower($category->name))] = $category->id;
            }
            $categoryCsv = base_path('resources/views/themes/' . self::$_theme->theme . '/import/blocks/categories.csv');
            if (file_exists($categoryCsv) && ($fileHandle = fopen($categoryCsv, 'r')) !== false) {
                $row = 0;
                while (($data = fgetcsv($fileHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Block Category') continue;
                    if (!empty($data[0])) {
                        list($name, $order) = $data;
                        self::$_csvCategoryData[trim(strtolower($name))] = $order;
                    }
                }
                fclose($fileHandle);
            }
        }

        if (empty(self::$_categoryIds[trim(strtolower($categoryName))])) {
            $newBlockCategory = new BlockCategory;
            $newBlockCategory->name = trim($categoryName);
            $newBlockCategory->order = !empty(self::$_csvCategoryData[trim(strtolower($categoryName))])?self::$_csvCategoryData[trim(strtolower($categoryName))]:0;
            $newBlockCategory->save();
            self::$_categoryIds[trim(strtolower($categoryName))] = $newBlockCategory->id;
        }

        return self::$_categoryIds[trim(strtolower($categoryName))];
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
                            if (!in_array($block, self::$_fileTemplateBlocks[$template])) {
                                self::$_fileTemplateBlocks[$template][] = $block;
                                self::$_template = $template;
                                self::block($block, []);
                            }
                        }
                    } else {
                        $templates = explode(',', $fields['templates']);
                        if (!empty($templates)) {
                            foreach ($templates as $template) {
                                if (isset(self::$_fileTemplateBlocks[$template])) {
                                    if (!in_array($block, self::$_fileTemplateBlocks[$template])) {
                                        self::$_fileTemplateBlocks[$template][] = $block;
                                        self::$_template = $template;
                                        self::block($block, []);
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
                    if (in_array($block, self::$_repeaterTemplates)) {
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

    private static function _processDatabaseBlocks()
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

        $templates = Template::where('theme_id', '=', self::$_theme->id)->get();
        if (!$templates->isEmpty()) {
            foreach ($templates as $template) {
                self::$_databaseTemplates[$template->template] = $template;
                self::$_databaseTemplateIds[$template->id] = $template;
            }
        }
        self::saveTemplates(); // save any new templates
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

        $globalBlocks = ThemeBlock::where('theme_id', '=', self::$_theme->id)->get();
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

    public static function getThemeName()
    {
        return !empty(self::$_theme) ? self::$_theme->theme : '';
    }

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
            self::$theme = Theme::find($themeId);
            self::_processDatabaseBlocks();
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
                $themeBlocks[$newBlock]['templates'] = implode(', ', self::$_fileBlockTemplates[$newBlock]);
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
                $themeBlocks[$existingBlock]['templates'] = implode(', ', self::$_fileBlockTemplates[$existingBlock]);
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
                $themeBlocks[$existingBlock]['templates'] = implode(', ', self::$_fileBlockTemplates[$existingBlock]);
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
                    $themeBlocks[$existingBlock]['rowClass'] = 2;
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
        $coreTemplates = self::$_fileCoreBlockTemplates[$blockName];
        if (in_array('__core_repeater', $coreTemplates) && count($coreTemplates) == 1) {
            if (empty($themeBlocks[$blockName]['templates'])) {
                $themeBlocks[$blockName]['templates'] = 'block only found inside repeaters, no template updates required';
            }
        } elseif (in_array('__core_category', $coreTemplates)) {
            if (empty($themeBlocks[$blockName]['templates'])) {
                $themeBlocks[$blockName]['templates'] = 'block only found inside categories templates, can\'t determine which page templates use this block';
            } else {
                $themeBlocks[$blockName]['templates'] .= ', block also found in categories template so it may be used in other page templates';
            }
            if ($themeBlocks[$blockName]['rowClass'] == 5) {
                $themeBlocks[$blockName]['rowClass'] = 4; // info, there may be changes in blocks in the category templates
            }
        } else {
            $coreTemplateFound = false;
            foreach (self::$_coreTemplates as $coreTemplate) {
                if (in_array($coreTemplate, $coreTemplates)) {
                    $coreTemplateFound = true;
                    break;
                }
            }
            if ($coreTemplateFound) {
                if (empty($themeBlocks[$blockName]['templates'])) {
                    $themeBlocks[$blockName]['templates'] = 'can\'t determine which page templates use this block (block may use a custom page_id)';
                } else {
                    $themeBlocks[$blockName]['templates'] .= ', the block may be used in other page templates (block may use a custom page_id in places)';
                }
                if ($themeBlocks[$blockName]['rowClass'] == 5) {
                    $themeBlocks[$blockName]['rowClass'] = 4; // info, there may be changes to some core templates
                }
            }
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
            $processedData['category_id'] = self::_categoryGuess($block);
            $processedData['label'] = ucwords(str_replace('_', ' ', $block));
            $processedData['name'] = $block;
            $processedData['type'] = !empty($details['type']) ? $details['type'] : self::_typeGuess($block);
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

    private static function _categoryGuess($block)
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

            $order = 0;
            foreach ($findKeys as $key => $matches) {
                $order += 10;
                if (!isset(self::$_guessedCategoryIds[$key])) {
                    $newBlockCategory = new BlockCategory;
                    $newBlockCategory->name = ucwords($key);
                    $newBlockCategory->order = ($key=='seo')?100:$order;
                    $newBlockCategory->save();
                    self::$_guessedCategoryIds[$key] = $newBlockCategory->id;
                    self::$_categoryIds[$newBlockCategory->name] = $newBlockCategory->id;
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

    private static function _typeGuess($block)
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
        return $typeFound;
    }

    /*
     * Update Functions
     */

    public static function saveTemplates()
    {
        if (!empty(self::$_fileTemplateBlocks)) {
            foreach (self::$_fileTemplateBlocks as $template => $blocks) {
                if (empty(self::$_databaseTemplates[$template]) && strpos($template, '__core_') !== 0) {
                    $newTemplate = new Template;
                    $newTemplate->theme_id = self::$_theme->id;
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

    public static function updateBlockTemplates($block, $blockData)
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
                $newThemeBlock->theme_id = self::$_theme->id;
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
                ThemeBlock::where('block_id', '=', self::$_allBlocks[$block]->id)->where('theme_id', '=', self::$_theme->id)->delete();
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

    /*
     * Deal with standard PageBuilder Methods
     */

    public static function block($block_name, $options = array())
    {
        if (!empty($options['import_ignore'])) {
            return;
        }

        $block_name = strtolower($block_name);

        if (in_array($block_name, self::$_repeaterTemplates)) {
            $currentRepeater = self::$_repeater;
            self::$_repeater = $block_name;

            // manually call the repeater view as the normal pagebuilder won't call it if the block_repeaters table is empty
            if (!empty($options['view'])) {
                $repeaterView = $options['view'];
            } else {
                $repeaterView = $block_name;
            }
            $output = View::make(
                'themes.' . self::$_theme->theme . '.blocks.repeaters.' . $repeaterView,
                ['is_first' => true, 'is_last' => true, 'count' => 1, 'total' => 1, 'id' => 1, 'pagination' => '']
            )->render();

            self::$_repeater = $currentRepeater;
        } else {

            // always use blank data for processing blocks
            if (isset(self::$_blockSettings[$block_name]['type'])) {
                $block = new Block;
                $block->type = self::$_blockSettings[$block_name]['type'];
            } else {
                $block = Block::preload($block_name);
            }
            if (empty($block)) {
                $block = new Block;
                $block->type = self::_typeGuess($block_name);
            }

            $block_class = $block->get_class();
            $output = $block_class::display($block, '', $options);
        }

        if (self::$_repeater) {
            // if in a repeater template
            if (!isset(self::$_repeaterBlocks[self::$_repeater])) {
                self::$_repeaterBlocks[self::$_repeater] = [];
            }
            if (!in_array($block_name, self::$_repeaterBlocks[self::$_repeater])) {
                self::$_repeaterBlocks[self::$_repeater][] = $block_name;
            }
            $template = '__core_repeater';
        } else {
            // if in a normal template
            if (!array_key_exists('page_id', $options)) {
                $template = self::$_template;
            } else {
                $template = '__core_otherPage';
            }
        }

        if (!in_array($block_name, self::$_fileTemplateBlocks[$template])) {
            self::$_fileTemplateBlocks[$template][] = $block_name;
        }

        if (!empty($options['import_note'])) {
            if (!isset(self::$_blockSettings[$block_name])) {
                self::$_blockSettings[$block_name] = [];
            }
            if (!isset(self::$_blockSettings[$block_name]['note'])) {
                self::$_blockSettings[$block_name]['note'] = $options['import_note'];
            }
        }

        if (!empty($options['import_return_value'])) {
            $output = $options['import_return_value'];
        }

        return $output;
    }

    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'block_') === 0) {
            $validTypes = BlockManager::getBlockClasses();
            $blockType = strtolower(substr($name, 6));
            if (!empty($validTypes[$blockType])) {
                $blockName = $arguments[0];
                if (!isset(self::$_blockSettings[$blockName])) {
                    self::$_blockSettings[$blockName] = [];
                }
                if (!isset(self::$_blockSettings[$blockName]['type'])) {
                    self::$_blockSettings[$blockName]['type'] = $blockType;
                }
                return forward_static_call_array(['self', 'block'], $arguments);
            }
        }
        if ($name == 'category') {
            $tmp = self::$_template;
            self::$_template = '__core_category';
            $output = forward_static_call_array(['\CoasterCms\Libraries\Builder\PageBuilder', 'category'], $arguments);
            self::$_template = $tmp;
            return $output;
        }
        try {
            return forward_static_call_array(['\CoasterCms\Libraries\Builder\PageBuilder', $name], $arguments);
        } catch (\Exception $e) {
            self::$_error = $e->getMessage();
            return '';
        }
    }

}
