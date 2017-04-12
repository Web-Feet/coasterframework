<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\File\Csv;
use CoasterCms\Helpers\Cms\Theme\BlockUpdater;
use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Helpers\Cms\File\Zip;
use CoasterCms\Libraries\Import\BlocksImport;
use CoasterCms\Libraries\Import\ContentImport;
use CoasterCms\Libraries\Import\GroupsImport;
use CoasterCms\Libraries\Import\MenusImport;
use CoasterCms\Libraries\Import\PagesImport;
use DB;
use Eloquent;
use Request;
use URL;
use Validator;

Class Theme extends Eloquent
{
    protected $table = 'themes';
    private static $_uploadsToAdd;

    public function templates()
    {
        return $this->belongsToMany('CoasterCms\Models\Template', 'theme_templates')
            ->withPivot('label', 'child_template')
            ->addSelect('templates.id')
            ->addSelect('templates.template')
            ->addSelect(DB::raw('IF (`theme_templates`.`label` IS NOT NULL, `theme_templates`.`label`, `templates`.`label`) as label'))
            ->addSelect(DB::raw('IF (`theme_templates`.`child_template` IS NOT NULL, `theme_templates`.`child_template`, `templates`.`child_template`) as child_template'))
            ->addSelect(DB::raw('IF (`theme_templates`.`hidden` IS NOT NULL, `theme_templates`.`hidden`, `templates`.`hidden`) as hidden'))
            ->addSelect('templates.updated_at')
            ->addSelect('templates.created_at');
    }

    public function templateById($templateId)
    {
        return $this->templates()->where('templates.id', '=', $templateId)->first();
    }

    public function blocks()
    {
        return $this->belongsToMany('CoasterCms\Models\Block', 'theme_blocks')->withPivot('show_in_pages', 'exclude_theme_templates', 'show_in_global')->where('active', '=', 1)->orderBy('order', 'asc');
    }

    public static function get_template_list($includeTemplate = 0)
    {
        $templates = [];
        if ($theme = static::find(config('coaster::frontend.theme'))) {
            foreach ($theme->templates()->having('hidden', '=', 0)->get() as $template) {
                $templates[$template->id] = !empty($template->label) ? $template->label : $template->template;
            }
        }
        if ($includeTemplate && !array_key_exists($includeTemplate, $templates)) {
            $templates[$includeTemplate] = 'Hidden or non existent template (ID: '.$includeTemplate.')';
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
                if ($in_pages && !empty($theme_block->pivot->exclude_theme_templates)) {
                    $ex_templates = explode(",", $theme_block->pivot->exclude_theme_templates);
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

    public static function templateIdUpdate($themeId = 0, $force = false)
    {
        $themeTemplatesByName = [];
        $themeTemplatesById = [];
        $templatesById = [];

        if (!$themeId) {
            $themeSetting = Setting::where('name', '=', 'frontend.theme')->first();
            if (!empty($themeSetting)) {
                $themeId = $themeSetting->value;
            }
        }

        $templates = Template::all();
        if (!$templates->isEmpty() && $themeId) {

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

    public static function upload($newTheme)
    {
        $file = Request::file('newTheme');
        $validator = Validator::make(['theme' => $newTheme], ['theme' => 'required']);
        if (!$validator->fails() && $file->getClientOriginalExtension() == 'zip') {
            $uploadTo = base_path() . '/resources/views/themes/';
            $file->move($uploadTo, $file->getClientOriginalName());
            $error = self::unzip($file->getClientOriginalName());
        } else {
            $error = 'The theme uploaded must be a zip file format.';
        }
        return $error;
    }

    public static function unzip($themeZip, $errorOnExisting = true)
    {
        $filePathInfo = pathinfo($themeZip);
        $uploadTo = base_path() . '/resources/views/themes/';
        $themeDir = $uploadTo . str_replace('.', '_', $filePathInfo['filename']);
        $error = '';
        if (!is_dir($themeDir)) {
            $zip = new \ZipArchive;
            if ($zip->open($uploadTo . $themeZip) === true) {
                $extractItems = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if (strpos($zip->getNameIndex($i), $filePathInfo['filename'] . '/') === 0) {
                        $extractItems[] = $zip->getNameIndex($i);
                    }
                }
                if (!empty($extractItems)) {
                    $zip->extractTo($uploadTo, $extractItems);
                    if (($uploadTo . $filePathInfo['filename']) != $themeDir) {
                        Directory::copy($uploadTo . $filePathInfo['filename'], $themeDir);
                        Directory::remove($uploadTo . $filePathInfo['filename']);
                    }
                } else {
                    $zip->extractTo($themeDir);
                }
                $zip->close();
                unlink($uploadTo . $themeZip);
            } else {
                $error = 'Error uploading zip file, file may bigger than the server upload limit.';
            }
        } elseif ($errorOnExisting) {
            $error = 'Theme with the same name already exists';
        }
        return $error;
    }

    public static function install($themeName, $options)
    {
        $themePath = base_path() . '/resources/views/themes/'.$themeName;
        $packed = is_dir($themePath.'/views') && is_dir($themePath.'/public');

        if (!empty($options['check'])) {
            $pagesImport = ($packed)?$themePath.'/views/import/pages':$themePath.'/import/pages';
            return ['error' => 0, 'response' => is_dir($pagesImport)];
        }

        if ($packed) {

            // extract public folder, extract uploads folder, and move views to themes root
            Directory::copy($themePath . '/public', public_path() . '/themes/' . $themeName);
            Directory::remove($themePath . '/public');

            if (is_dir($themePath . '/uploads')) {
                $securePaths = [];
                $secureUploadPaths = explode(',', config('coaster::site.secure_folders'));
                foreach ($secureUploadPaths as $secureUploadPath) {
                    $securePaths[] = '/uploads/' . trim($secureUploadPath, '/');
                }

                Directory::copy($themePath . '/uploads', public_path() . '/uploads', function ($addFrom, $addTo) use ($securePaths, $themePath) {
                    $uploadPath = str_replace(public_path(), '', $addTo);
                    foreach ($securePaths as $securePath) {
                        if (strpos($uploadPath, $securePath) === 0) {
                            $addTo = str_replace(public_path() . '/uploads', storage_path() . '/uploads', $addTo);
                            break;
                        }
                    }
                    return [$addFrom, $addTo];
                });
            }
            Directory::remove($themePath . '/uploads');

            Directory::copy($themePath . '/views', $themePath);
            Directory::remove($themePath . '/views');

        }

        $unpacked = is_dir($themePath.'/templates') && is_dir(public_path().'/themes/'.$themeName);

        if (!$unpacked) {
            return ['error' => 1, 'response' => 'theme files not found for ' . $themeName];
        }

        $theme = self::where('theme', '=', $themeName)->first();

        if (empty($theme)) {

            // add theme to database
            $newTheme = new self;
            $newTheme->theme = $themeName;
            $newTheme->save();

            // install theme blocks and templates
            try {
                $blocksImport = new BlocksImport();
                $blocksImport->setTheme($newTheme)->run();
            } catch (\Exception $e) {
                $newTheme->delete();
                return ['error' => 1, 'response' => $e->getMessage()];
            }

            // install pages and page block data
            if (!empty($options['withPageData'])) {
                try {

                    $pagesImport = new PagesImport();
                    $pagesImport->run();
                    $pagesImport = new GroupsImport();
                    $pagesImport->run();
                    $pagesImport = new MenusImport();
                    $pagesImport->run();
                    $pagesImport = new ContentImport();
                    $pagesImport->run();

                } catch (\Exception $e) {
                    return ['error' => 1, 'response' => $e->getMessage()];
                }
            }

            return ['error' => 0, 'response' => ''];
        } else {
            return ['error' => 0, 'response' => 'theme ' . $themeName . ' already exists in database'];
        }

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

    public static function remove($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme)) {
            if ($theme->id == config('coaster::frontend.theme')) {
                return 0;
            }
            ThemeTemplateBlock::where('theme_id', '=', $theme->id)->delete();
            ThemeTemplate::where('theme_id', '=', $theme->id)->delete();
            ThemeBlock::where('theme_id', '=', $theme->id)->delete();
            $theme->delete();
        }
        if (is_dir(base_path() . '/resources/views/themes/' . $themeName)) {
            Directory::remove(base_path() . '/resources/views/themes/' . $themeName);
        }
        if (is_dir(base_path() . '/public/themes/' . $themeName)) {
            Directory::remove(base_path() . '/public/themes/' . $themeName);
        }
        return 1;
    }

    public static function getViewFolderTree($dir, $ret = array())
    {
      $tmp = new \stdClass;
      $dirArr = explode('/', $dir);
      $tmp->directory = end($dirArr);
      $tmp->path = $dir;
      $tmp->files = \File::files($dir);
      foreach ($tmp->files as $fk => $file)
      {
        $filArr = explode('/', $file);
        $tmp->files[$fk] = end($filArr);
      }
      $tmp->folders = \File::directories($dir);
      foreach ($tmp->folders as $fk => $folder)
      {
        $tmp->folders[$fk] = static::getViewFolderTree($folder, $ret);
      }
      $ret = $tmp;

      return $ret;
    }

    public static function export($themeId, $withPageData)
    {
        $theme = self::find($themeId);
        if (!empty($theme)) {
            $themesDir = base_path() . '/resources/views/themes/';
            $zipFileName = $theme->theme.'.zip';
            // export blocks
            BlockUpdater::exportBlocks($theme);
            if ($withPageData) {
                // export page data
                self::_pageExportData($theme);
            }
            $zip = new Zip;
            $zip->open($themesDir . $zipFileName, Zip::CREATE);
            $zip->addDir($themesDir . $theme->theme, 'views', function($addFrom, $addTo) use($withPageData) {
                if ($addTo == 'views/import') {
                    $addTo = '';
                }
                if (stripos($addTo, '/.svn/') !== false) {
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
            $zip->addDir(public_path() . '/themes/' . $theme->theme, 'public', function($addFrom, $addTo) {
                if (stripos($addTo, '/.svn/') !== false) {
                    $addTo = '';
                }
                return [$addFrom, $addTo];
            });
            if (!empty(self::$_uploadsToAdd)) {
                foreach (self::$_uploadsToAdd as $zipPath => $dirPath) {
                    if (file_exists($dirPath) && !is_dir($dirPath)) {
                        $zip->addFile($dirPath, $zipPath);
                    }
                }
            }
            $zip->close();

            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=" . $zipFileName);
            header('Content-Length: ' . filesize($themesDir . $zipFileName));
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($themesDir . $zipFileName);
            unlink($themesDir . $zipFileName);
            Directory::remove($themesDir.$theme->theme.'/export');
            exit;
        }
        return 'error';
    }

    private static function _pageExportData($theme)
    {
        @mkdir(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages');
        $pagesCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages.csv', 'w');
        $pageBlocksCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/page_blocks.csv', 'w');
        $repeaterBlocksCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/repeater_blocks.csv', 'w');
        $menusCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/menus.csv', 'w');
        $menuItemsCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/menu_items.csv', 'w');

        $allFilesUsed = [];

        $groupIds = [];
        $groups = PageGroup::all();
        foreach($groups as $group) {
            $groupIds[$group->id] = $group->id;
        }
        $pagesData = [];
        $pages = Page::all();
        foreach($pages as $page) {
            $pagesData[$page->id] = $page;
        }
        $pageLangData = [];
        $pageLangs = PageLang::where('language_id', '=', Language::current())->orderBy('page_id')->get();
        foreach($pageLangs as $pageLang) {
            $pageLangData[$pageLang->page_id] = $pageLang;
        }
        $templatesById = [];
        $templatesByName = [];
        $templates = Template::where('theme_id', '=', $theme->id)->get();
        foreach ($templates as $template) {
            $templatesById[$template->id] = $template->template;
            $templatesByName[$template->template] = $template->id;
        }

        // export pages

        fputcsv($pagesCsv, [
            'Page Id',
            'Page Name',
            'Page Url',
            'Page Template',
            'Parent Page Id',
            'Default Child Template',
            'Page Order Value',
            'Is Link (0 or 1)',
            'Is Live (0 or 1)',
            'In Sitemap (0 or 1)',
            'Container for Group Id',
            'Container Url Priority',
            'Canonical Parent Page Id',
            'Group Ids (Comma Separated)'
        ]);

        foreach ($pageLangs as $pageLang) {
            if (!empty($pagesData[$pageLang->page_id])) {
                $page = $pagesData[$pageLang->page_id];
                if ($page->link && (strpos($pageLang->url, URL::to('/')) === 0 || strpos($pageLang->url, '/') === 0)) {
                    $filesUsed = [str_replace(URL::to('/'), '', $pageLang->url)];
                    $allFilesUsed = array_merge($filesUsed, $allFilesUsed);
                }
                fputcsv($pagesCsv, [
                    $pageLang->page_id,
                    $pageLang->name,
                    $pageLang->url,
                    !empty($templatesById[$page->template])?$templatesById[$page->template]:'',
                    $page->parent?:'',
                    !empty($templatesById[$page->child_template])?$templatesById[$page->child_template]:'',
                    $page->order,
                    $page->link,
                    $page->live?1:0,
                    $page->sitemap,
                    ($page->group_container && !empty($groupIds[$page->group_container]))?$page->group_container:'',
                    ($page->group_container_url_priority && !empty($groupIds[$page->group_container]))?$page->group_container_url_priority:'',
                    $page->canonical_parent,
                    implode(',', $page->groupIds())
                ]);
            }
        }

        // export menus

        fputcsv($menusCsv, [
            'Menu Identifier',
            'Menu Name',
            'Menu Max Sublevels'
        ]);

        $menuIds = [];
        $menuIdentifiers = [];
        $menus = Menu::all();
        foreach ($menus as $menu) {
            $menuIds[] = $menu->id;
            $menuIdentifiers[$menu->id] = $menu->name;
            fputcsv($menusCsv, [
                $menu->name,
                $menu->label,
                $menu->max_sublevel
            ]);
        }

        fputcsv($menuItemsCsv, [
            'Menu Identifier',
            'Item Page Id',
            'Item Order',
            'Item Sublevels',
            'Item Custom Name',
            'Custom Subpage Names',
            'Hidden Pages'
        ]);

        $menuItems = MenuItem::whereIn('menu_id', $menuIds)->get()->all();

        usort($menuItems, function ($a, $b) {
            if ($a->menu_id == $b->menu_id) {
                if ($a->order == $b->order) {
                    return 0;
                }
                return $a->order < $b->order ? -1 : 1;
            }
            return $a->menu_id < $b->menu_id ? -1 : 1;
        });

        foreach($menuItems as $menuItem) {
            fputcsv($menuItemsCsv, [
                $menuIdentifiers[$menuItem->menu_id],
                $menuItem->page_id,
                $menuItem->order,
                $menuItem->sub_levels,
                $menuItem->custom_name,
                $menuItem->custom_page_names,
                $menuItem->hidden_pages
            ]);
        }

        // export page groups
        if (!$groups->isEmpty()) {

            $groupsCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/groups.csv', 'w');
            $groupAttributesCsv = fopen(base_path().'/resources/views/themes/'.$theme->theme.'/export/pages/group_attributes.csv', 'w');

            fputcsv($groupsCsv, [
                'Group Id',
                'Group Name',
                'Group Item Name',
                'Url Priority (Default: 50)',
                'Default Template'
            ]);

            fputcsv($groupAttributesCsv, [
                'Attribute Id',
                'Group Id',
                'Block Name',
                'Order Priority (0 for no ordering)',
                'Order Dir (asc/desc)',
                'Container Filter by Block Name'
            ]);

            $groupAttributesByGroupId = [];
            $groupAttributes = PageGroupAttribute::orderBy('group_id')->get();
            foreach ($groupAttributes as $groupAttribute) {
                if (!isset($groupAttributesByGroupId[$groupAttribute->group_id])) {
                    $groupAttributesByGroupId[$groupAttribute->group_id] = [];
                }
                $groupAttributesByGroupId[$groupAttribute->group_id][] = $groupAttribute;
            }

            foreach ($groups as $pageGroup) {

                fputcsv($groupsCsv, [
                    $pageGroup->id,
                    $pageGroup->name,
                    $pageGroup->item_name,
                    $pageGroup->url_priority,
                    !empty($templatesById[$pageGroup->default_template])?$templatesById[$pageGroup->default_template]:''
                ]);

                if (!empty($groupAttributesByGroupId[$pageGroup->id])) {
                    foreach ($groupAttributesByGroupId[$pageGroup->id] as $groupAttribute) {
                        fputcsv($groupAttributesCsv, [
                            $groupAttribute->id,
                            $pageGroup->id,
                            Block::preload($groupAttribute->item_block_id)->name,
                            $groupAttribute->item_block_order_priority,
                            $groupAttribute->item_block_order_dir,
                            $groupAttribute->filter_by_block_id?:''
                        ]);
                    }
                }
            }
            fclose($groupsCsv);
            fclose($groupAttributesCsv);
        }

        // export page block data

        fputcsv($pageBlocksCsv, [
            'Page Id',
            'Block Name',
            'Content'
        ]);

        $blocksById = [];
        $blocksByName = [];
        foreach (BlockUpdater::getDatabaseBlocks($theme->id) as $block) {
            $blocksById[$block->id] = $block;
            $blocksByName[$block->name] = $block;
        }

        $blockTemplatesById = [];
        $templateBlocks = TemplateBlock::whereIn('template_id', $templatesByName)->get();
        if (!$templateBlocks->isEmpty()) {
            foreach ($templateBlocks as $templateBlock) {
                if (!isset($blockTemplatesById[$templateBlock->template_id])) {
                    $blockTemplatesById[$templateBlock->template_id] = [];
                }
                $blockTemplatesById[$templateBlock->template_id][] = $templateBlock->block_id;
            }
        }
        $themeBlocks = ThemeBlock::where('theme_id', '=', $theme->id)->where('show_in_pages', '=', 1)->get();
        if (!$themeBlocks->isEmpty()) {
            foreach ($themeBlocks as $themeBlock) {
                $ignoreTemplates = explode(',', $themeBlock->exclude_templates);
                foreach ($templatesByName as $templateById) {
                    if (!in_array($templateById, $ignoreTemplates)) {
                        if (!isset($blockTemplatesById[$templateById])) {
                            $blockTemplatesById[$templateById] = [];
                        }
                        $blockTemplatesById[$templateById][] = $themeBlock->block_id;
                    }
                }

            }
        }

        $pageBlocks = collect(array_merge(Block::getDataForVersion(new PageBlock, 0)->all(), Block::getDataForVersion(new PageBlockDefault, 0)->all()));
        $repeaterBlocks = [];

        $pageBlockArr = [];
        foreach ($pageBlocks as $pageBlock) {
            $blockName = !empty($blocksById[$pageBlock->block_id])?$blocksById[$pageBlock->block_id]->name:null;

            if (!empty($blockName) && !empty($pageBlock->content)) {

                // don't add data for blocks that aren't set as template blocks even if data exists (sort of a cleanup on export)
                if (isset($pageBlock->page_id)) {
                    if (empty($pagesData[$pageBlock->page_id]->template) || empty($blockTemplatesById[$pagesData[$pageBlock->page_id]->template])) {
                        continue;
                    } elseif (!in_array($pageBlock->block_id, $blockTemplatesById[$pagesData[$pageBlock->page_id]->template])) {
                        continue;
                    }
                }

                if (strtolower($blocksById[$pageBlock->block_id]->type) == 'repeater') {
                    $repeaterBlocks[$pageBlock->content] = PageBlockRepeaterData::loadRepeaterData($pageBlock->content);
                    $repeaterBlockArr[] = $pageBlock->block_id;
                }

                $filesUsed = $blocksById[$pageBlock->block_id]->getTypeObject()->exportFiles($pageBlock->content);
                $allFilesUsed = array_merge($filesUsed, $allFilesUsed);

                $pageBlockArr[] = [
                    isset($pageBlock->page_id)?$pageBlock->page_id:0,
                    $blockName,
                    $pageBlock->content
                ];
            }
        }

        usort($pageBlockArr, function ($a, $b) {
            if ($a[0] == $b[0]) {
                return strcmp($a[1], $b[1]);
            }
            return $a[0] < $b[0] ? -1 : 1;
        });

        foreach ($pageBlockArr as $pageBlock) {
            fputcsv($pageBlocksCsv, $pageBlock);
        }

        fputcsv($repeaterBlocksCsv, [
            'Repeater Id',
            'Repeater Row',
            'Block Name',
            'Content'
        ]);

        ksort($repeaterBlocks);
        foreach ($repeaterBlocks as $repeaterId => $repeaterRows) {
            foreach ($repeaterRows as $repeaterRowId => $repeaterBlocks) {
                foreach ($repeaterBlocks as $repeaterBlockId => $repeaterContent) {
                    $blockName = !empty($blocksById[$repeaterBlockId])?$blocksById[$repeaterBlockId]->name:null;
                    if (!empty($blockName) && $repeaterContent) {

                        $filesUsed = $blocksById[$repeaterBlockId]->getTypeObject()->exportFiles($repeaterContent);
                        $allFilesUsed = array_merge($filesUsed, $allFilesUsed);

                        fputcsv($repeaterBlocksCsv, [
                            $repeaterId,
                            $repeaterRowId,
                            $blockName,
                            $repeaterContent
                        ]);
                    }
                }
            }
        }

        // backup uploaded files
        $securePaths = [];
        $secureUploadPaths = explode(',', config('coaster::site.secure_folders'));
        foreach ($secureUploadPaths as $secureUploadPath) {
            $securePaths[] = '/uploads/'.trim($secureUploadPath, '/');
        }

        $allFilesUsed = array_unique($allFilesUsed);
        if (!empty($allFilesUsed)) {
            foreach ($allFilesUsed as $file) {
                $base_path = public_path();
                foreach ($securePaths as $securePath) {
                    if (strpos($file, $securePath) === 0) {
                        $base_path = storage_path();
                        break;
                    }
                }
                if (file_exists($base_path.$file)) {
                    self::$_uploadsToAdd[trim($file, '/')] = $base_path.$file;
                }
            }
        }

        fclose($menuItemsCsv);
        fclose($menusCsv);
        fclose($pagesCsv);
        fclose($pageBlocksCsv);
        fclose($repeaterBlocksCsv);
    }

    public static function _pageImportData($theme)
    {
        $importPath = $themePath = base_path() . '/resources/views/themes/'.$theme->theme.'/import/';

        if (is_dir($importPath)) {

            // wipe data
            DB::table((new Page)->getTable())->truncate();
            DB::table((new PageLang)->getTable())->truncate();
            DB::table((new PageVersion)->getTable())->truncate();
            DB::table((new PagePublishRequests())->getTable())->truncate();
            DB::table((new PageGroup)->getTable())->truncate();
            DB::table((new PageGroupAttribute)->getTable())->truncate();
            DB::table((new PageGroupPage)->getTable())->truncate();
            DB::table((new Menu)->getTable())->truncate();
            DB::table((new MenuItem)->getTable())->truncate();
            DB::table((new PageBlockDefault)->getTable())->truncate();
            DB::table((new PageBlock)->getTable())->truncate();
            DB::table((new PageBlockRepeaterData)->getTable())->truncate();
            DB::table((new PageBlockRepeaterRows)->getTable())->truncate();

            $templateIds = [];
            $templates = Template::where('theme_id', '=', $theme->id)->get();
            foreach ($templates as $template) {
                $templateIds[$template->template] = $template->id;
            }

            $blockIds = [];
            $blocks = Block::all();
            foreach ($blocks as $block) {
                $blockIds[$block->name] = $block->id;
            }

            $pagesCsv = $importPath . 'pages.csv';
            $groupsCsv = $importPath . 'pages/groups.csv';
            $groupAttributesCsv = $importPath . 'pages/group_attributes.csv';
            $menusCsv = $importPath . 'pages/menus.csv';
            $menuItemsCsv = $importPath . 'pages/menu_items.csv';
            $pageBlocksCsv = $importPath . 'pages/page_blocks.csv';
            $repeaterBlocksCsv = $importPath . 'pages/repeater_blocks.csv';

            // checks
            $error = 'pages data not imported, invalid columns in: ';
            if (!$pagesFileHandle = Csv::check($pagesCsv, 14)) {
                if (file_exists($pagesCsv)) {
                    if ($pagesFileHandle = Csv::check($pagesCsv, 12)) {
                        // TODO individual column checks + defaults for optional columns
                        $defaultTheme = true;
                    } else {
                        throw new \Exception($error . $pagesCsv);
                    }
                }
            }
            if (!$groupsHandle = Csv::check($groupsCsv, 5)) {
                if (file_exists($groupsCsv)) {
                    throw new \Exception($error . $groupsCsv);
                }
            }
            if (!$groupAttributesHandle = Csv::check($groupAttributesCsv, 6)) {
                if (file_exists($groupAttributesCsv)) {
                    throw new \Exception($error . $groupAttributesCsv);
                }
            }
            if (!$menusHandle = Csv::check($menusCsv, 3)) {
                if (file_exists($menusCsv)) {
                    throw new \Exception($error . $menusCsv);
                }
            }
            if (!$menuItemsCsvHandle = Csv::check($menuItemsCsv, 7)) {
                if (file_exists($menuItemsCsv)) {
                    if (!($menuItemsCsv = Csv::check($pagesCsv, 5))) {
                        throw new \Exception($error . $menuItemsCsv);
                    }
                }
            }
            if (!$pageBlocksCsvHandle = Csv::check($pageBlocksCsv, 3)) {
                if (file_exists($pageBlocksCsv)) {
                    throw new \Exception($error . $pageBlocksCsv);
                }
            }
            if (!$repeaterBlocksCsvHandle = Csv::check($repeaterBlocksCsv, 4)) {
                if (file_exists($repeaterBlocksCsv)) {
                    throw new \Exception($error . $repeaterBlocksCsv);
                }
            }

            // add pages
            if ($pagesFileHandle) {
                $row = 0;
                while (($data = fgetcsv($pagesFileHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Page Id') continue;
                    if (isset($defaultTheme)) {
                        $data = array_merge($data, ['', '']);
                    }
                    list($pageId, $pageName, $pageUrl, $templateName, $parentId, $defaultChildTemplateName, $order, $link, $live, $sitemap, $groupContainer, $groupContainerUrlPriority, $canonicalParentPageId, $groupIds) = $data;
                    $newPage = new Page;
                    $newPage->id = $pageId;
                    $newPage->template = !empty($templateIds[$templateName]) ? $templateIds[$templateName] : 0;
                    $newPage->parent = $parentId ?: 0;
                    $newPage->child_template = !empty($templateIds[$defaultChildTemplateName]) ? $templateIds[$defaultChildTemplateName] : 0;
                    $newPage->order = $order;
                    $newPage->link = $link;
                    $newPage->live = $live;
                    $newPage->sitemap = $sitemap;
                    $newPage->group_container = $groupContainer ?: 0;
                    $newPage->group_container_url_priority = $groupContainerUrlPriority ?: 0;
                    $newPage->canonical_parent = $canonicalParentPageId ?: 0;
                    $newPage->save();
                    $newPageLang = new PageLang;
                    $newPageLang->page_id = $pageId;
                    $newPageLang->language_id = Language::current();
                    $newPageLang->name = $pageName;
                    $newPageLang->url = $pageUrl;
                    $newPageLang->live_version = 1;
                    $newPageLang->save();
                    PageVersion::add_new($pageId);
                    $groupIds = trim($groupIds);
                    $groupIds = $groupIds ? explode(',', $groupIds) : [];
                    foreach ($groupIds as $groupId) {
                        $newPageGroupPage = new PageGroupPage;
                        $newPageGroupPage->page_id = $pageId;
                        $newPageGroupPage->group_id = $groupId;
                        $newPageGroupPage->save();
                    }
                }
            }

            // add page groups
            if ($groupsHandle) {
                $row = 0;
                while (($data = fgetcsv($groupsHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Group Id') continue;
                    list($groupId, $groupName, $itemName, $defaultContainerPageId, $defaultTemplate) = $data;
                    $newGroup = new PageGroup;
                    $newGroup->id = $groupId;
                    $newGroup->name = $groupName;
                    $newGroup->item_name = $itemName;
                    $newGroup->url_priority = $defaultContainerPageId;
                    $newGroup->default_template = !empty($templateIds[$defaultTemplate]) ? $templateIds[$defaultTemplate] : 0;
                    $newGroup->save();
                }
            }
            if ($groupAttributesHandle) {
                $row = 0;
                while (($data = fgetcsv($groupAttributesHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Attribute Id') continue;
                    list($attributeId, $groupId, $blockName, $orderPriority, $orderDir, $filerByBlockName) = $data;
                    $newGroupAttribute = new PageGroupAttribute;
                    $newGroupAttribute->id = $attributeId;
                    $newGroupAttribute->group_id = $groupId;
                    $newGroupAttribute->item_block_id = !empty($blockIds[$blockName]) ? $blockIds[$blockName] : 0;
                    $newGroupAttribute->item_block_order_priority = $orderPriority;
                    $newGroupAttribute->item_block_order_dir = ($orderDir == 'desc' ? $orderDir : 'asc');
                    $newGroupAttribute->filter_by_block_id = !empty($blockIds[$filerByBlockName]) ? $blockIds[$filerByBlockName] : 0;
                    $newGroupAttribute->save();
                }
            }

            // add menus
            if ($menusHandle) {
                $menuIds = [];
                $row = 0;
                while (($data = fgetcsv($menusHandle)) !== false) {
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
            if ($menuItemsCsvHandle) {
                $row = 0;
                while (($data = fgetcsv($menuItemsCsvHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Menu Identifier') continue;
                    if (count($data) == 5) $data = array_merge($data, ['', '']);
                    list($menuIdentifier, $pageId, $order, $subLevels, $customName, $customPageNames, $hiddenPages) = $data;
                    if (!empty($menuIds[$menuIdentifier])) {
                        $newMenuItem = new MenuItem;
                        $newMenuItem->menu_id = $menuIds[$menuIdentifier];
                        $newMenuItem->page_id = $pageId;
                        $newMenuItem->order = $order;
                        $newMenuItem->sub_levels = $subLevels;
                        $newMenuItem->custom_name = $customName;
                        $newMenuItem->custom_page_names = $customPageNames;
                        $newMenuItem->hidden_pages = $hiddenPages;
                        $newMenuItem->save();
                    }
                }
            }

            // add page content
            if ($pageBlocksCsvHandle) {
                $row = 0;
                while (($data = fgetcsv($pageBlocksCsvHandle)) !== false) {
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
            if ($repeaterBlocksCsvHandle) {
                $row = 0;
                $existingRepeaterRowKeys = [];
                while (($data = fgetcsv($repeaterBlocksCsvHandle)) !== false) {
                    if ($row++ == 0 && $data[0] == 'Repeater Id') continue;
                    list($repeaterId, $repeaterRowId, $blockName, $content) = $data;
                    if (!empty($blockIds[$blockName])) {
                        if ($decodedContent = json_decode($content)) {
                            if (!is_string($decodedContent)) {
                                $content = serialize($decodedContent);
                            }
                        }
                        if (!isset($existingRepeaterRowKeys[$repeaterId . '-' . $repeaterRowId])) {
                            $newRepeaterRow = new PageBlockRepeaterRows;
                            $newRepeaterRow->repeater_id = $repeaterId;
                            $newRepeaterRow->row_id = $repeaterRowId;
                            $newRepeaterRow->save();
                            $existingRepeaterRowKeys[$repeaterId . '-' . $repeaterRowId] = $newRepeaterRow->id;
                            $newRepeaterData = new PageBlockRepeaterData;
                            $newRepeaterData->row_key = $existingRepeaterRowKeys[$repeaterId . '-' . $repeaterRowId];
                            $newRepeaterData->block_id = 0;
                            $newRepeaterData->version = 1;
                            $newRepeaterData->content = $repeaterRowId;
                            $newRepeaterData->save();
                        }
                        $newRepeaterData = new PageBlockRepeaterData;
                        $newRepeaterData->row_key = $existingRepeaterRowKeys[$repeaterId . '-' . $repeaterRowId];
                        $newRepeaterData->block_id = $blockIds[$blockName];
                        $newRepeaterData->version = 1;
                        $newRepeaterData->content = $content;
                        $newRepeaterData->save();
                    }
                }
            }

            PageSearchData::updateAllSearchData();
        }
    }

}
