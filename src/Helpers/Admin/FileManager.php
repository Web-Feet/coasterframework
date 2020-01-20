<?php namespace CoasterCms\Helpers\Admin;

use Auth;
use CoasterCms\Helpers\Cms\File\SecureUpload;

class FileManager
{

    public static function accessCheck()
    {
        if (!Auth::action('filemanager')) {
            if (Auth::admin()) {
                die('Access denied');
            } else {
                header('Location: //' . $_SERVER['HTTP_HOST'] . '/' . config('coaster::admin.url') . '/login?login_path=' . $_SERVER['REQUEST_URI']);
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
            'ext_misc' => ['zip', 'rar', 'gz', 'tar', 'iso', 'dmg', 'kml', 'gpx'],
            'aviary_apiKey' => config('wf_cms::key.aviary'),
            'aviary_maxSize' => '2000',
            'hidden_folders' => ['.svn'],
            'java_upload' => false,
        ] + static::getPermissions();

        foreach ($coasterSettings as $setting => $value) {
            if (!in_array($setting, $ignore)) {
                $config[$setting] = $value;
            }
        }
    }

    public static function getPermissions($override = null)
    {
        $editPermission = !!(is_null($override) ? Auth::action('filemanager.edit') : $override);

        return[
            'delete_files'                            => $editPermission,
            'create_folders'                          => $editPermission,
            'delete_folders'                          => $editPermission,
            'upload_files'                            => $editPermission,
            'rename_files'                            => $editPermission,
            'rename_folders'                          => $editPermission,
            'duplicate_files'                         => $editPermission,
            'extract_files'                           => $editPermission,
            'copy_cut_files'                          => $editPermission, // for copy/cut files
            'copy_cut_dirs'                           => $editPermission, // for copy/cut directories
            'chmod_files'                             => false, // change file permissions
            'chmod_dirs'                              => false, // change folder permissions
            'preview_text_files'                      => true, // eg.: txt, log etc.
            'edit_text_files'                         => $editPermission, // eg.: txt, log etc.
            'create_text_files'                       => $editPermission, // only create files with exts. defined in $config['editable_text_file_exts']
            'download_files'			              => true, // allow download files or just preview
        ];
    }

    public static function setSecureUpload($subdir)
    {
        global $config;
        $config['current_path'] = SecureUpload::isSecurePath($subdir) ? SecureUpload::getBasePath(true) . '/' : $config['current_path'];
    }

    public static function createDirPermissions()
    {
        return 0775;
    }

}