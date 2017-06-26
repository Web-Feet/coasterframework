<?php namespace CoasterCms\Console\Assets;

use CoasterCms\Helpers\Cms\File\File;

class FileManager extends AbstractAsset
{

    public static $name = 'filemanager';

    public static $version = 'v9.10.1';

    public static $description = 'Responsive File Manager';

    public function run()
    {
        $this->downloadZip(
            'https://github.com/trippo/ResponsiveFilemanager/releases/download/v9.10.1/responsive_filemanager.zip',
            ['filemanager' => '', 'tinymce/plugins/responsivefilemanager' => '../jquery/tinymce/plugins/responsivefilemanager']
        );

        $this->_setDetailedMessage('integrating with Coaster');

        File::insertAtLine($this->_baseFolder . '/config/config.php', [
            362 => [
                'require __DIR__ .\'/../../../../vendor/web-feet/coasterframework/hooks/laravel.php\';',
                '\CoasterCms\Helpers\Admin\FileManager::accessCheck();',
                '\CoasterCms\Helpers\Admin\FileManager::setConfig($config, []);',
                ''
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/dialog.php', [
            84 => [
                '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($subdir);'
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/execute.php', [
            33 => [
                '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'path\']);'
            ]
        ]);
        File::insertAtLine($this->_baseFolder . '/upload.php', [
            19 => [
                '   \CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'path\']);'
            ],
            24 => [
                '   \CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'fldr\']);'
            ]
        ]);

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

        $this->copyFrom($this->_publicFiles('uploads'), public_path('uploads'), false, false);
        $this->copyFrom($this->_publicFiles('uploads/secure'), storage_path('uploads/secure'), false, false);

        // namespace has changed for FileManager helper, check config files have up to date one
        $this->_checkConfigFiles(public_path('uploads'));
        $this->_checkConfigFiles(storage_path('uploads'));
    }

    protected function _checkConfigFiles($uploadPath)
    {
        foreach (scandir($uploadPath) as $item) {
            if (!in_array($item, ['.', '..'])) {
                if (is_dir($uploadPath . '/' . $item)) {
                    $this->_checkConfigFiles($uploadPath . '/' . $item);
                } elseif ($item == 'config.php') {
                    $this->_updateConfigFile($uploadPath . '/' . $item);
                }
            }
        }
    }

    protected function _updateConfigFile($configFile)
    {
        $count = 0;
        $fileContent = file_get_contents($configFile);
        $fileContent = preg_replace(
            '/\S*filemanager_set_permissions\((.*)\);/m',
            '\CoasterCms\Helpers\Admin\FileManager::filemanager_set_permissions(\1);',
            $fileContent,
            -1,
            $count
        );
        if ($count) {
            file_put_contents($configFile, $fileContent);
        }
    }

}