<?php namespace CoasterCms\Helpers\Admin;

use Auth;

class FileManager
{

    public static function accessCheck()
    {
        if (!Auth::action('filemanager')) {
            if (Auth::admin()) {
                die('Access denied');
            } else {
                header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' . config('coaster::admin.url') . '/login?login_path=' . $_SERVER['REQUEST_URI']);
            }
        }
    }

    public static function setConfig(&$config, $ignore)
    {
        $coasterSettings = [
            'base_url' => '',
            'upload_dir' => '/uploads/',
            'current_path' => '../../uploads/',
            'thumbs_base_path' => '../../cache/fm_thumbs/',
            'MaxSizeUpload' => 10,
            'show_folder_size' => true,
            'transliteration' => true,
            'convert_spaces' => true,
            'image_max_width' => 2000,
            'image_max_height' => 2000,
            'chmod_files' => false,
            'chmod_dirs' => false,
            'ext_misc' => ['zip', 'rar', 'gz', 'tar', 'iso', 'dmg', 'kml', 'gpx'],
            'aviary_apiKey' => config('wf_cms::key.aviary'),
            'aviary_maxSize' => '2000',
            'hidden_folders' => ['.svn'],
            'java_upload' => false,
        ];
        foreach ($coasterSettings as $setting => $value) {
            if (!in_array($setting, $ignore)) {
                $config[$setting] = $value;
            }
        }
    }

    public static function filemanager_set_permissions($override = null)
    {
        global $delete_files,
               $create_folders,
               $delete_folders,
               $upload_files,
               $rename_files,
               $rename_folders,
               $duplicate_files,
               $copy_cut_files,
               $copy_cut_dirs,
               $preview_text_files,
               $edit_text_files,
               $create_text_files;
        if($override || (Auth::action('filemanager.edit') && $override === null)) {
            $delete_files		= true;
            $create_folders		= true;
            $delete_folders		= true;
            $upload_files		= true;
            $rename_files		= true;
            $rename_folders		= true;
            $duplicate_files	= true;
            $copy_cut_files		= true;
            $copy_cut_dirs		= true;
            $preview_text_files	= true;
            $edit_text_files 	= true;
            $create_text_files 	= true;
        } else {
            $delete_files		= false;
            $create_folders		= false;
            $delete_folders		= false;
            $upload_files		= false;
            $rename_files		= false;
            $rename_folders		= false;
            $duplicate_files	= false;
            $copy_cut_files		= false;
            $copy_cut_dirs		= false;
            $preview_text_files	= false;
            $edit_text_files 	= false;
            $create_text_files 	= false;
        }
    }

    public static function setSecureUpload($checkPath)
    {
        global $current_path;

        $secureStorage = storage_path('uploads').'/';
        if (strpos($checkPath,$secureStorage) === 0) {
            $subdir = substr($checkPath,strlen($secureStorage));
        } else {
            $subdir = $checkPath;
        }

        $secureFolders = explode(',', config('coaster::site.secure_folders'));

        foreach ($secureFolders as $secureFolder) {
            if ($secureFolder == '*') {
                $secureFolder = '/';
            }
            if (strpos($subdir, trim($secureFolder, '/').'/') === 0) {
                if (!is_dir(__DIR__.$secureStorage.'')) {
                    @mkdir($secureStorage.$subdir);
                }
                $current_path = $secureStorage;
                break;
            }
        }
    }

    public static function createDirPermissions()
    {
        return 0775;
    }

}