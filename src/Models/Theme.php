<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Helpers\Cms\File\SecureUpload;
use CoasterCms\Helpers\Cms\File\Zip;
use CoasterCms\Helpers\Cms\Page\PageCache;
use CoasterCms\Libraries\Export\BlocksExport;
use CoasterCms\Libraries\Export\ContentExport;
use CoasterCms\Libraries\Export\GroupsExport;
use CoasterCms\Libraries\Export\MenusExport;
use CoasterCms\Libraries\Export\PagesExport;
use CoasterCms\Libraries\Import\BlocksImport;
use CoasterCms\Libraries\Import\ContentImport;
use CoasterCms\Libraries\Import\GroupsImport;
use CoasterCms\Libraries\Import\MenusImport;
use CoasterCms\Libraries\Import\PagesImport;
use DB;
use Eloquent;
use Illuminate\Http\JsonResponse;
use Request;
use URL;
use Validator;

Class Theme extends Eloquent
{
    protected $table = 'themes';

    public function templates()
    {
        $templatesTable = DB::getTablePrefix() . (new Template)->getTable();
        $themeTemplatesTable = DB::getTablePrefix() . (new ThemeTemplate)->getTable();
        return $this->belongsToMany('CoasterCms\Models\Template', 'theme_templates')
            ->withPivot('label', 'child_template')
            ->addSelect('templates.id')
            ->addSelect('templates.template')
            ->addSelect(DB::raw('IF (`'.$themeTemplatesTable.'`.`label` IS NOT NULL, `'.$themeTemplatesTable.'`.`label`, `'.$templatesTable.'`.`label`) as label'))
            ->addSelect(DB::raw('IF (`'.$themeTemplatesTable.'`.`child_template` IS NOT NULL, `'.$themeTemplatesTable.'`.`child_template`, `'.$templatesTable.'`.`child_template`) as child_template'))
            ->addSelect(DB::raw('IF (`'.$themeTemplatesTable.'`.`hidden` IS NOT NULL, `'.$themeTemplatesTable.'`.`hidden`, `'.$templatesTable.'`.`hidden`) as hidden'))
            ->addSelect('templates.updated_at')
            ->addSelect('templates.created_at');
    }

    public function templateById($templateId)
    {
        return $this->templates()->where('templates.id', '=', $templateId)->first();
    }

    public function blocks()
    {
        return $this->belongsToMany('CoasterCms\Models\Block', 'theme_blocks')->withPivot('show_in_pages', 'exclude_templates', 'show_in_global')->where('active', '=', 1)->orderBy('order', 'asc');
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
            return '';
        } else {
            return 'The theme uploaded must be a zip file format.';
        }
    }

    public static function unzip(&$themeName)
    {
        $uploadsPath = base_path() . '/resources/views/themes/';
        $themeTmpDir = $uploadsPath . $themeName;
        $newThemeName = str_replace('.', '_', $themeName); // view classes can't cope with dots
        $themeZip = $themeTmpDir . '.zip';
        $themeDir = $uploadsPath . $newThemeName;
        if (!is_dir($themeDir)) {
            $zip = new \ZipArchive;
            if ($zip->open($themeZip) === true) {
                $extractItems = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if (strpos($zip->getNameIndex($i), $themeName . '/') === 0) {
                        $extractItems[] = $zip->getNameIndex($i);
                    }
                }
                if (!empty($extractItems)) {
                    $zip->extractTo($uploadsPath, $extractItems);
                    if ($themeTmpDir != $themeDir) {
                        Directory::copy($themeTmpDir, $themeDir);
                        Directory::remove($themeTmpDir);
                    }
                } else {
                    $zip->extractTo($themeDir);
                }
                $themeName = $newThemeName;
                $zip->close();
                unlink($themeZip);
            } else {
                throw new \Exception('Error unzipping theme, file may be corrupt, try uploading again.');
            }
        } elseif (file_exists($themeZip)) {
            throw new \Exception('Unzipped theme with same name already exists.');
        }
    }

    public static function install($themeName, $options)
    {
        try {
            static::unzip($themeName);
        } catch (\Exception $e) {
            return new JsonResponse([$e->getMessage()], 500);
        }

        $themePath = base_path() . '/resources/views/themes/' . $themeName;
        $packedFolders = [
            '/views',
            '/public',
            '/uploads'
        ];
        $packed = false;
        foreach ($packedFolders as $packedFolder) {
            $packed = $packed || is_dir($themePath . $packedFolder);
        }

        if (!empty($options['check'])) {
            $pagesImport = is_dir($themePath . '/views') ? $themePath.'/views/import/pages' : $themePath . '/import/pages';
            return new JsonResponse(is_dir($pagesImport));
        }

        if ($packed) {

            // extract public folder, extract uploads folder, and move views to themes root
            if (is_dir($themePath . '/public')) {
                Directory::copy($themePath . '/public', public_path() . '/themes/' . $themeName);
                Directory::remove($themePath . '/public');
            }

            if (is_dir($themePath . '/uploads')) {
                Directory::copy($themePath . '/uploads', public_path() . '/uploads', function ($addFrom, $addTo) use ($themePath) {
                    $addTo = substr($addFrom, strlen($themePath));
                    $addTo = SecureUpload::getBasePath(SecureUpload::isSecurePath($addTo), false) . $addTo;
                    return [$addFrom, $addTo];
                });
                Directory::remove($themePath . '/uploads');
            }

            if (is_dir($themePath . '/views')) {
                Directory::copy($themePath . '/views', $themePath);
                Directory::remove($themePath . '/views');
            }

        }

        $unpacked = is_dir($themePath.'/templates') && is_dir(public_path().'/themes/'.$themeName);

        if (!$unpacked) {
            return new JsonResponse(['Themes template folder or public folder not found'], 500);
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
                if ($blocksImport->getErrorMessages()) {
                    throw new \Exception;
                }
                $blocksImport->save(true);
                $blocksImport->cleanCsv();
            } catch (\Exception $e) {
                $newTheme->delete();
                return new JsonResponse($e->getMessage() ? [$e->getMessage()] : $blocksImport->getErrorMessages(), 500);
            }

            // install pages and page block data
            if (!empty($options['withPageData'])) {
                try {
                    $errors = [];
                    $importClasses = [
                        PagesImport::class,
                        GroupsImport::class,
                        MenusImport::class,
                        ContentImport::class
                    ];
                    foreach ($importClasses as $importClass) {
                        $importObject = new $importClass($themePath . '/import/');
                        $importObject->run();
                        $importObject->deleteCsv();
                        $errors = array_merge($errors, $importObject->getErrorMessages());
                    }
                    PageSearchData::updateAllSearchData();
                    if ($errors) {
                        throw new \Exception;
                    }
                } catch (\Exception $e) {
                    return new JsonResponse($e->getMessage() ? [$e->getMessage()] : $errors, 500);
                }
            }

            return new JsonResponse('Theme installed');
        } else {
            return new JsonResponse('Theme with same name exists in database (' . $themeName . ')');
        }

    }

    public static function activate($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme)) {
            Setting::where('name', '=', 'frontend.theme')->update(['value' => $theme->id]);
            PageCache::clear();
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
            ThemeTemplateBlock::whereIn('theme_template_id', ThemeTemplate::where('theme_id', '=', $theme->id)->get()->pluck('id')->toArray())->delete();
            ThemeTemplate::where('theme_id', '=', $theme->id)->delete();
            ThemeBlock::where('theme_id', '=', $theme->id)->delete();
            $theme->delete();
        }
        $themePath = base_path() . '/resources/views/themes/' . $themeName;
        if (file_exists($themePath . '.zip')) {
            unlink($themePath . '.zip');
        }
        Directory::remove($themePath);
        Directory::remove(base_path() . '/public/themes/' . $themeName);
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

            $themesDir = base_path() . '/resources/views/themes/' . $theme->theme;
            $themePublicDir = public_path() . '/themes/' . $theme->theme;
            $themeExportDataFolder = $themesDir . '/export';
            $zipFilePath = $themesDir . '.zip';
            $zip = new Zip;
            $zip->open($zipFilePath, Zip::CREATE);

            // save block data to export folder
            $blocksExport = new BlocksExport($themeExportDataFolder);
            $blocksExport->setTheme($theme)->run();
            // save page data to export folder
            if ($withPageData) {
                $exportClasses = [
                    PagesExport::class,
                    GroupsExport::class,
                    MenusExport::class,
                    ContentExport::class
                ];
                foreach ($exportClasses as $exportClass) {
                    $exportObject = new $exportClass($themeExportDataFolder);
                    $exportObject->run();
                    // add uploaded to zip
                    if ($uploads = $exportObject->getUploads()) {
                        foreach ($uploads as $uploadPath) {
                            $basePath = SecureUpload::getBasePath(SecureUpload::isSecurePath($uploadPath), false);
                            if (file_exists($basePath . $uploadPath) && !is_dir($basePath . $uploadPath)) {
                                $zip->addFile($basePath . $uploadPath, $uploadPath);
                            }
                        }
                    }
                }
            }
            // zip theme dir and rename export folder to import
            $zip->addDir($themesDir, 'views', function($addFrom, $addTo) use($withPageData) {
                if (stripos($addTo, '/.') !== false) {
                    $addTo = '';
                } elseif ($addTo == 'views/import' || (!$withPageData && stripos($addTo, 'views/export/pages') === 0)) {
                    $addTo = '';
                } elseif (stripos($addTo, 'views/export') === 0) {
                    $addTo = 'views/import' . substr($addTo, 12);
                }
                return [$addFrom, $addTo];
            });
            // zip public files
            $zip->addDir($themePublicDir, 'public', function($addFrom, $addTo) {
                if (stripos($addTo, '/.') !== false) {
                    $addTo = '';
                }
                return [$addFrom, $addTo];
            });
            $zip->close();

            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=" . $theme->theme . '.zip');
            header('Content-Length: ' . filesize($zipFilePath));
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($zipFilePath);
            unlink($zipFilePath);
            Directory::remove($themesDir.$theme->theme.'/export');
            exit;
        }
        return 'error';
    }

}
