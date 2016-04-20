<?php
// v5.2.30 => v5.2.31

$updatedFileManagerConf = false;
$uploadsDir = public_path('uploads');
findConfigPhp($uploadsDir);

function findConfigPhp($dir) {
    foreach (scandir($dir) as $dirItem) {
        if (!in_array($dirItem, ['.', '..'])) {
            if (is_dir($dir . '/' . $dirItem)) {
                findConfigPhp($dir . '/' . $dirItem);
            } elseif ($dirItem == 'config.php') {
                updateConfigPhp($dir . '/' . $dirItem);
            }
        }
    }
}

function updateConfigPhp($configFile) {
    global $updatedFileManagerConf;
    $fileContent = file_get_contents($configFile);
    if (strpos($fileContent, 'filemanager_set_permissions(') !== false && strpos($fileContent, '\CoasterCms\Helpers\FileManager::filemanager_set_permissions(') === false) {
        \CoasterCms\Helpers\File::replaceString($configFile, 'filemanager_set_permissions(', '\CoasterCms\Helpers\FileManager::filemanager_set_permissions(');
        $updatedFileManagerConf = true;
    }
}

if ($updatedFileManagerConf) {
    echo "Coaster Framework: Updated file manager config files\n";
}