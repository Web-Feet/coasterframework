<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\File;
use CoasterCms\Helpers\Zip;
use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Libraries\Builder\ThemeBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

Class Theme extends Eloquent
{
    protected $table = 'themes';

    public function templates()
    {
        return $this->hasMany('CoasterCms\Models\Template');
    }

    public function blocks()
    {
        return $this->belongsToMany('CoasterCms\Models\Block', 'theme_blocks')->withPivot('show_in_pages', 'exclude_templates', 'show_in_global')->where('active', '=', 1)->orderBy('order', 'asc');
    }

    public static function get_template_list($includeTemplate = 0)
    {
        $templates = array();
        $theme = self::find(config('coaster::frontend.theme'));
        if (!empty($theme)) {
            foreach ($theme->templates()->where('hidden', '=', 0)->get() as $template) {
                $templates[$template->id] = !empty($template->label) ? $template->label : $template->template;
                $templateFile[$template->template] = $template->id;
            }
        }
        // fix template issues on theme switching
        if (!empty($includeTemplate) && empty($templates[$includeTemplate])) {
            $includeTemplateModel = Template::find($includeTemplate);
            if (empty($includeTemplateModel)) {
                $templates[$includeTemplate] = 'Non existent template';
            } elseif (!empty($templateFile[$includeTemplateModel->template])) {
                $templates[$includeTemplate] = $templates[$templateFile[$includeTemplateModel->template]].' (using closest match in current theme)';
            } else {
                $templates[$includeTemplate] = (!empty($includeTemplateModel->label) ? $includeTemplateModel->label : $includeTemplateModel->template).' (not found in current theme - frontend won\'t load)';
            }
        }
        asort($templates);
        return $templates;
    }

    public static function theme_blocks($theme, $template = 0)
    {
        $in_pages = !empty($template) ? true : false;
        $blocks = array();
        $selected_theme = self::find($theme);
        if (!empty($selected_theme)) {
            $theme_blocks = $selected_theme->blocks()->get();
            foreach ($theme_blocks as $theme_block) {
                if ($in_pages && !empty($theme_block->pivot->exclude_templates)) {
                    $ex_templates = explode(",", $theme_block->pivot->exclude_templates);
                    if (!empty($ex_templates) && in_array($template, $ex_templates)) {
                        $theme_block->pivot->show_in_pages = 0;
                    }
                }
                if ((!$in_pages && $theme_block->pivot->show_in_global == 1) || ($in_pages && $theme_block->pivot->show_in_pages == 1)) {
                    if (!isset($blocks[$theme_block->category_id])) {
                        $blocks[$theme_block->category_id] = array();
                    }
                    $blocks[$theme_block->category_id][$theme_block->id] = $theme_block;
                }
            }
        }
        return $blocks;
    }

    public static function selectArray()
    {
        $array = [];
        foreach (self::all() as $theme) {
            $array[$theme->id] = $theme->theme;
        }
        return $array;
    }

    public static function upload($newTheme)
    {
        $error = '';
        $file = Request::file('newTheme');
        $validator = Validator::make(['theme' => $newTheme], ['theme' => 'required']);
        if (!$validator->fails() && $file->getClientOriginalExtension() == 'zip') {
            $filePathInfo = pathinfo($file->getClientOriginalName());
            $uploadTo = base_path() . '/resources/views/themes/';
            $themeDir = $uploadTo . str_replace('.', '_', $filePathInfo['filename']);
            if (!is_dir($themeDir)) {
                $file->move($uploadTo, $file->getClientOriginalName());
                $zip = new \ZipArchive;
                if ($zip->open($uploadTo . $file->getClientOriginalName()) === true) {
                    $extractItems = [];
                    for($i = 0; $i < $zip->numFiles; $i++) {
                        if (strpos($zip->getNameIndex($i), $filePathInfo['filename'].'/') === 0) {
                            $extractItems[] = $zip->getNameIndex($i);
                        }
                    }
                    if (!empty($extractItems)) {
                        $zip->extractTo($uploadTo, $extractItems);
                        if (($uploadTo . $filePathInfo['filename']) != $themeDir) {
                            File::copyDirectory($uploadTo . $filePathInfo['filename'], $themeDir);
                            File::removeDirectory($uploadTo . $filePathInfo['filename']);
                        }
                    } else {
                        $zip->extractTo($themeDir);
                    }
                    $zip->close();
                    unlink($uploadTo . $file->getClientOriginalName());
                } else {
                    $error = 'Error uploading zip file, file may bigger than the server upload limit.';
                }
            } else {
                $error = 'Theme with the same name already exists';
            }
        } else {
            $error = 'The theme uploaded must be a zip file format.';
        }
        return $error;
    }

    public static function install($themeName, $options)
    {
        $themePath = base_path() . '/resources/views/themes/'.$themeName;
        $theme = self::where('theme', '=', $themeName)->first();
        if (empty($theme) && is_dir($themePath) && is_dir($themePath.'/views') && is_dir($themePath.'/public')) {

            if (!empty($options['check'])) {
                if (is_dir($themePath.'/views/import/pages')) {
                    return 2;
                }
                return 1;
            }

            // extract public folder and move views to themes root
            File::copyDirectory($themePath.'/public', public_path().'/themes/'.$themeName);
            File::removeDirectory($themePath.'/public');
            File::copyDirectory($themePath.'/views', $themePath);
            File::removeDirectory($themePath.'/views');

            // add theme to database
            $newTheme = new self;
            $newTheme->theme = $themeName;
            $newTheme->save();

            // install theme blocks and templates
            try {
                ThemeBuilder::updateTheme($newTheme->id);
                ThemeBuilder::cleanOverwriteFile($newTheme->id);
            } catch (\Exception $e) {
                // ignore no blocks found ?
            }

            // install page data
            $importPath = $themePath.'/import/';
            if (!empty($options['withPageData']) && is_dir($importPath)) {

                // wipe data
                DB::table((new Page)->getTable())->truncate();
                DB::table((new PageLang)->getTable())->truncate();
                DB::table((new PageVersion)->getTable())->truncate();
                DB::table((new PageGroup)->getTable())->truncate();
                DB::table((new PageGroupAttribute)->getTable())->truncate();
                DB::table((new Menu)->getTable())->truncate();
                DB::table((new MenuItem)->getTable())->truncate();
                DB::table((new PageBlockDefault)->getTable())->truncate();
                DB::table((new PageBlock)->getTable())->truncate();
                DB::table((new PageBlockRepeaterData)->getTable())->truncate();
                DB::table((new PageBlockRepeaterRows)->getTable())->truncate();

                $templateIds = [];
                $templates = Template::where('theme_id', '=', $newTheme->id)->get();
                foreach ($templates as $template) {
                    $templateIds[$template->template] = $template->id;
                }

                $blockIds = [];
                $blocks = Block::all();
                foreach ($blocks as $block) {
                    $blockIds[$block->name] = $block->id;
                }

                // add pages
                $pagesCsv = $importPath.'pages.csv';
                if (file_exists($pagesCsv) && ($fileHandle = fopen($pagesCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Page Id') continue;
                        list($pageId, $pageName, $pageUrl, $templateName, $parentId, $defaultChildTemplateName, $order, $link, $live, $groupContainer, $groupItem) = $data;
                        $newPage = new Page;
                        $newPage->id = $pageId;
                        $newPage->template = !empty($templateIds[$templateName])?$templateIds[$templateName]:0;
                        $newPage->parent = $parentId;
                        $newPage->child_template = !empty($templateIds[$defaultChildTemplateName])?$templateIds[$defaultChildTemplateName]:0;
                        $newPage->order = $order;
                        $newPage->link = $link;
                        $newPage->live = $live;
                        $newPage->group_container = $groupContainer;
                        $newPage->in_group = $groupItem;
                        $newPage->save();
                        $newPageLang = new PageLang;
                        $newPageLang->page_id = $pageId;
                        $newPageLang->language_id = Language::current();
                        $newPageLang->name = $pageName;
                        $newPageLang->url = $pageUrl;
                        $newPageLang->live_version = 1;
                        $newPageLang->save();
                        PageVersion::add_new($pageId);
                    }
                }

                // add page groups
                $groupsCsv = $importPath.'pages/groups.csv';
                if (file_exists($groupsCsv) && ($fileHandle = fopen($groupsCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Group Id') continue;
                        list($groupId, $groupName, $itemName, $defaultContainerPageId, $defaultTemplate, $orderAttributeId, $orderDirection) = $data;
                        $newGroup = new PageGroup;
                        $newGroup->id = $groupId;
                        $newGroup->name = $groupName;
                        $newGroup->item_name = $itemName;
                        $newGroup->default_parent = $defaultContainerPageId;
                        $newGroup->default_template = !empty($templateIds[$defaultTemplate])?$templateIds[$defaultTemplate]:0;
                        $newGroup->order_by_attribute_id = $orderAttributeId;
                        $newGroup->order_dir = $orderDirection;
                        $newGroup->save();
                    }
                }
                $groupAttributesCsv = $importPath.'pages/group_attributes.csv';
                if (file_exists($groupAttributesCsv) && ($fileHandle = fopen($groupAttributesCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Attribute Id') continue;
                        list($attributeId, $groupId, $blockName, $filerByBlockName) = $data;
                        $newGroupAttribute = new PageGroupAttribute;
                        $newGroupAttribute->id = $attributeId;
                        $newGroupAttribute->group_id = $groupId;
                        $newGroupAttribute->item_block_id = !empty($blockIds[$blockName])?$blockIds[$blockName]:0;
                        $newGroupAttribute->filter_by_block_id = !empty($blockIds[$filerByBlockName])?$blockIds[$filerByBlockName]:0;
                        $newGroupAttribute->save();
                    }
                }

                // add menus
                $menusCsv = $importPath.'pages/menus.csv';
                $menuIds = [];
                if (file_exists($menusCsv) && ($fileHandle = fopen($menusCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Menu Identifier') continue;
                        list($name, $label, $maxSublevel) = $data;
                        $newMenu = new Menu;
                        $newMenu->label = $label;
                        $newMenu->name = $name;
                        $newMenu->max_sublevel = $maxSublevel;
                        $newMenu->save();
                        $menuIds[$name] = $newMenu->id;
                    }
                }
                $menuItemsCsv = $importPath.'pages/menu_items.csv';
                if (file_exists($menuItemsCsv) && ($fileHandle = fopen($menuItemsCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Menu Identifier') continue;
                        list($menuIdentifier, $pageId, $order, $subLevels, $customName) = $data;
                        if (!empty($menuIds[$menuIdentifier])) {
                            $newMenuItem = new MenuItem;
                            $newMenuItem->menu_id = $menuIds[$menuIdentifier];
                            $newMenuItem->page_id = $pageId;
                            $newMenuItem->order = $order;
                            $newMenuItem->sub_levels = $subLevels;
                            $newMenuItem->custom_name = $customName;
                            $newMenuItem->save();
                        }
                    }
                }

                // add page content
                $pageBlocksCsv = $importPath.'pages/page_blocks.csv';
                if (file_exists($pageBlocksCsv) && ($fileHandle = fopen($pageBlocksCsv, 'r')) !== false) {
                    $row = 0;
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Page Id') continue;
                        list($pageId, $blockName, $content) = $data;
                        if (!empty($blockIds[$blockName])) {
                            if ($pageId) {
                                $newPageBlock = new PageBlock;
                                $newPageBlock->page_id = $pageId;
                            } else {
                                $newPageBlock = new PageBlockDefault;
                            }
                            $newPageBlock->block_id = $blockIds[$blockName];
                            $newPageBlock->version = 1;
                            $newPageBlock->content = $content;
                            $newPageBlock->save();
                        }
                    }
                }
                $repeaterBlocksCsv = $importPath.'pages/repeater_blocks.csv';
                if (file_exists($repeaterBlocksCsv) && ($fileHandle = fopen($repeaterBlocksCsv, 'r')) !== false) {
                    $row = 0;
                    $existingRepeaterRowKeys = [];
                    while (($data = fgetcsv($fileHandle)) !== false) {
                        if ($row++ == 0 && $data[0] == 'Repeater Id') continue;
                        list($repeaterId, $repeaterRowId, $blockName, $content) = $data;
                        if (!empty($blockIds[$blockName])) {
                            if ($decodedContent = json_decode($content)) {
                                if (!is_string($decodedContent)) {
                                    $content = serialize($decodedContent);
                                }
                            }
                            if (!isset($existingRepeaterRowKeys[$repeaterId.'-'.$repeaterRowId])) {
                                $newRepeaterRow = new PageBlockRepeaterRows;
                                $newRepeaterRow->repeater_id = $repeaterId;
                                $newRepeaterRow->row_id = $repeaterRowId;
                                $newRepeaterRow->save();
                                $existingRepeaterRowKeys[$repeaterId.'-'.$repeaterRowId] = $newRepeaterRow->id;
                                $newRepeaterData = new PageBlockRepeaterData;
                                $newRepeaterData->row_key = $existingRepeaterRowKeys[$repeaterId.'-'.$repeaterRowId];
                                $newRepeaterData->block_id = 0;
                                $newRepeaterData->version = 1;
                                $newRepeaterData->content = $repeaterRowId;
                                $newRepeaterData->save();
                            }
                            $newRepeaterData = new PageBlockRepeaterData;
                            $newRepeaterData->row_key = $existingRepeaterRowKeys[$repeaterId.'-'.$repeaterRowId];
                            $newRepeaterData->block_id = $blockIds[$blockName];
                            $newRepeaterData->version = 1;
                            $newRepeaterData->content = $content;
                            $newRepeaterData->save();
                        }
                    }
                }

            }

            return 1;
        }
        return 0;
    }

    public static function activate($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme)) {
            Setting::where('name', '=', 'frontend.theme')->update(['value' => $theme->id]);
            self::templateIdUpdate($theme->id);
            return 1;
        }
        return 0;
    }

    public static function templateIdUpdate($themeId = 0, $force = false)
    {
        $themeTemplatesByName = [];
        $themeTemplatesById = [];
        $templatesById = [];

        if (!$themeId) {
            $themeId = Setting::where('name', '=', 'frontend.theme')->get();
        }

        $templates = Template::all();
        if (!$templates->isEmpty()) {

            foreach ($templates as $template) {
                if ($template->theme_id == $themeId) {
                    $themeTemplatesByName[$template->template] = $template;
                    $themeTemplatesById[$template->id] = $template;
                }
                $templatesById[$template->id] = $template;
            }

            if (!empty($themeTemplatesById)) {

                // get default template id
                $defaultTemplate = Setting::where('name', '=', 'admin.default_template')->first();
                if (empty($defaultTemplate)) {
                    $defaultTemplateId = $defaultTemplate->value;
                } else {
                    $defaultTemplateId = config('coaster::admin.default_template');
                }

                // update default template id if not a theme template
                if (empty($defaultTemplateId) || !array_key_exists($defaultTemplateId, $themeTemplatesById)) {
                    if (!empty($templatesById[$defaultTemplateId])) {
                        $defaultTemplateName = $templatesById[$defaultTemplateId]->template;
                        if (!empty($themeTemplatesByName[$defaultTemplateName])) {
                            $newDefaultTemplateId = $themeTemplatesByName[$defaultTemplateName]->id;
                        }
                    }
                    if (empty($newDefaultTemplateId)) {
                        reset($themeTemplatesById);
                        $newDefaultTemplateId = key($themeTemplatesById);
                    }
                    Setting::where('name', '=', 'admin.default_template')->update(['value' => $newDefaultTemplateId]);
                    $defaultTemplateId = $newDefaultTemplateId;
                }

                // update all page templates if not in theme
                $pages = Page::all();
                foreach ($pages as $page) {
                    if ($page->template > 0 && !array_key_exists($page->template, $themeTemplatesById)) {
                        $newPageTemplateId = 0;
                        if (!empty($templatesById[$page->template])) {
                            $pageTemplateName = $templatesById[$page->template]->template;
                            if (!empty($themeTemplatesByName[$pageTemplateName])) {
                                $newPageTemplateId = $themeTemplatesByName[$pageTemplateName]->id;
                            }
                        }
                        if (empty($newPageTemplateId) && ($force || empty($templatesById[$page->template]))) {
                            $newPageTemplateId = $defaultTemplateId;
                        }
                        if (!empty($newPageTemplateId)) {
                            $page->template = $newPageTemplateId;
                            $page->save();
                        }
                    }
                }

            }

        }
    }

    public static function remove($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme)) {
            if ($theme->id == config('coaster::frontend.theme')) {
                return 0;
            }
            $templates = Template::where('theme_id', '=', $theme->id)->get();
            if (!$templates->isEmpty()) {
                $templateIds = [];
                foreach ($templates as $template) {
                    $templateIds[] = $template->id;
                }
                TemplateBlock::whereIn('template_id', $templateIds)->delete();
            }
            Template::where('theme_id', '=', $theme->id)->delete();
            ThemeBlock::where('theme_id', '=', $theme->id)->delete();
            $theme->delete();
        }
        if (is_dir(base_path() . '/resources/views/themes/' . $themeName)) {
            File::removeDirectory(base_path() . '/resources/views/themes/' . $themeName);
        }
        if (is_dir(base_path() . '/public/themes/' . $themeName)) {
            File::removeDirectory(base_path() . '/public/themes/' . $themeName);
        }
        return 1;
    }

    public static function export($themeId, $withPageData)
    {
        $theme = self::find($themeId);
        if (!empty($theme)) {
            $themesDir = base_path() . '/resources/views/themes/';
            $zipFileName = $theme->theme.'.zip';
            ThemeBuilder::processDatabase($themeId, $withPageData);
            $zip = new Zip;
            $zip->open($themesDir . $zipFileName, Zip::CREATE);
            $zip->addDir($themesDir . $theme->theme, 'views', function($addFrom, $addTo) use($withPageData) {
                if ($addTo == 'views/import') {
                    $addTo = '';
                }
                if (!$withPageData && stripos($addTo, 'views/export/pages') === 0) {
                    $addTo = '';
                }
                if (stripos($addTo, 'views/export') === 0) {
                    $addTo = 'views/import'.substr($addTo, 12);
                }
                return [$addFrom, $addTo];
            });
            $zip->addDir(public_path() . '/themes/' . $theme->theme, 'public');
            $zip->close();
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=" . $zipFileName);
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($themesDir . $zipFileName);
            unlink($themesDir . $zipFileName);
            File::removeDirectory($themesDir.$theme->theme.'/export');
            exit;
        }
        return 'error';
    }

}