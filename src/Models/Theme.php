<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\File;
use CoasterCms\Helpers\Zip;
use CoasterCms\Libraries\Builder\ThemeBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
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
        if (empty($theme) && is_dir($themePath) && is_dir($themePath.'/views')) {
            if (!empty($options['check'])) {
                if (is_dir($themePath.'/views/import/pages')) {
                    return 2;
                }
                return 1;
            }
            File::copyDirectory($themePath.'/public', public_path().'/themes/'.$themeName);
            File::removeDirectory($themePath.'/public');
            File::copyDirectory($themePath.'/views', $themePath);
            File::removeDirectory($themePath.'/views');
            $newTheme = new self;
            $newTheme->theme = $themeName;
            $newTheme->save();
            return $newTheme->id;
        }
        return 0;
    }

    public static function activate($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme)) {
            Setting::where('name', '=', 'frontend.theme')->update(['value' => $theme->id]);
            return 1;
        }
        return 0;
    }

    public static function remove($themeName)
    {
        $theme = self::where('theme', '=', $themeName)->first();
        if (!empty($theme) && $theme->id == config('coaster::frontend.theme')) {
            return 0;
        }
        if (!empty($theme)) {
            $templates = Template::where('theme_id', '=', $theme->id)->get();
            if (!$templates->isEmpty()) {
                $templateIds = [];
                foreach ($templates as $template) {
                    $templateIds[] = $template->id;
                }
                TemplateBlock::whereIn('template_id', $templateIds)->delete();
            }
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
        return 0;
    }

}