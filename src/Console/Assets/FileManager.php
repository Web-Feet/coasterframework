<?php namespace CoasterCms\Console\Assets;

use CoasterCms\Helpers\Cms\File\File;

class FileManager extends AbstractAsset
{

    public static $name = 'filemanager';

    public static $version = 'v9.14.0';

    public static $description = 'Responsive File Manager';

    public function run()
    {
        $this->downloadZip(
            'https://github.com/trippo/ResponsiveFilemanager/releases/download/v9.14.0/responsive_filemanager.zip',
            ['filemanager' => '', 'tinymce/plugins/responsivefilemanager' => '../jquery/tinymce/plugins/responsivefilemanager']
        );

        $this->_setDetailedMessage('integrating with Coaster');

        File::insertAtLine($this->_baseFolder . '/config/config.php', [
            558 => [
                'require __DIR__ .\'/../../../../vendor/web-feet/coasterframework/hooks/laravel.php\';',
                '\CoasterCms\Helpers\Admin\FileManager::accessCheck();',
                '\CoasterCms\Helpers\Admin\FileManager::setConfig($config, []);',
                ''
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/dialog.php', [
            86 => [
                '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($subdir);'
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/execute.php', [
            31 => [
                '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'path\']);'
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/upload.php', [
            28 => [
                '   \CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'fldr\']);'
            ]
        ]);

        // php 7.4 filemanager fixes
        File::insertAtLine($this->_baseFolder . '/UploadHandler.php', [
            495 => [
                '        if (!$content_range) return $name;'
            ]
        ]);
        File::replaceString(
            $this->_baseFolder . '/UploadHandler.php',
            '$uploaded_bytes = $this->fix_integer_overflow((int)$content_range[1]);',
            '$uploaded_bytes = $this->fix_integer_overflow($content_range ? (int)$content_range[1] : 0);'
        );

        // remove conflicting response class
        unlink($this->_baseFolder . '/include/Response.php');
        $clearResponseFile = fopen($this->_baseFolder . '/include/Response.php', 'w');
        fclose($clearResponseFile);

        // trans/endsWith func conflict name change
        File::replaceString($this->_baseFolder . '/include/utils.php', 'endsWith(', 'endsWithfm(');
        File::replaceString($this->_baseFolder . '/include/utils.php', '\'trans\'', '\'transfm\'');
        $files = [
            '/ajax_calls.php',
            '/dialog.php',
            '/execute.php',
            '/force_download.php',
            '/upload.php',
            '/include/utils.php'
        ];
        foreach ($files as $file) {
            File::replaceString($this->_baseFolder . $file, 'trans(', 'transfm(');
        }

        // permissions fix
        File::replaceString($this->_baseFolder . '/include/utils.php', 'umask(0)', 'umask()');
        File::replaceString($this->_baseFolder . '/include/utils.php', '0766', 'CoasterCms\Helpers\Admin\FileManager::createDirPermissions()');

        $this->_setDetailedMessage('setting up upload locations');

        // check config files are up to date
        $this->_backupOldConfigFiles(public_path('uploads'));
        $this->_backupOldConfigFiles(storage_path('uploads'));
        $this->copyFrom($this->_publicFiles('uploads'), public_path('uploads'), false, false);
        $this->copyFrom($this->_publicFiles('uploads/secure'), storage_path('uploads/secure'), false, false);
    }

    protected function _backupOldConfigFiles($uploadPath)
    {
        foreach (scandir($uploadPath) as $item) {
            if (!in_array($item, ['.', '..'])) {
                if (is_dir($uploadPath . '/' . $item)) {
                    $this->_backupOldConfigFiles($uploadPath . '/' . $item);
                } elseif ($item == 'config.php') {
                    rename($uploadPath . '/config.php', $uploadPath . '/config.php.old');
                }
            }
        }
    }
}
