<?php

if (version_compare(phpversion(), '5.5.9', '<')) {

    echo "Coaster Framework: Can't run updateAsset script with PHP Version ".phpversion()." (5.5.9+ required)\n";
    echo "Coaster Framework: manually run - php -f ".__DIR__."/updateAssets\n";

} elseif (!defined('LARAVEL_START')) {

    require __DIR__.'/../hooks/laravel_basic.php';

} else {

    $coasterStorageFolder = storage_path(config('coaster::site.storage_path'));
    if (!file_exists($coasterStorageFolder)) {
        mkdir($coasterStorageFolder, 0777, true);
    }

    $updatesFile = storage_path(config('coaster::site.storage_path') . '/updates.json');
    if (file_exists($updatesFile)) {
        $updateStatus = file_get_contents($updatesFile);
        $updateStatus = json_decode($updateStatus, true);
    } else {
        $updateStatus = [];
    }

    if (empty($updateStatus['storage']) || version_compare($updateStatus['storage'], 'v.5.2.39', '<')) {
        include __DIR__ . '/storageMove.php';
        $updateStatus['storage'] = config('coaster::site.version');
        file_put_contents($updatesFile, json_encode($updateStatus));
    }

    if (empty($updateStatus['fileManager']) || version_compare($updateStatus['fileManager'], 'v.5.2.31', '<')) {
        include __DIR__ . '/fileManagerConfig.php';
        $updateStatus['fileManager'] = config('coaster::site.version');
        file_put_contents($updatesFile, json_encode($updateStatus));
    }

    if (config('coaster::installed')) {

        if (empty($updateStatus['gallery']) || version_compare($updateStatus['gallery'], 'v.5.2.27', '<')) {
            include __DIR__ . '/galleryMoveFiles.php';
            $updateStatus['gallery'] = config('coaster::site.version');
            file_put_contents($updatesFile, json_encode($updateStatus));
        }

        if (empty($updateStatus['migrations']) || version_compare($updateStatus['migrations'], config('coaster::site.version'), '<')) {
            include __DIR__ . '/databaseMigrations.php';
            $updateStatus['migrations'] = config('coaster::site.version');
            file_put_contents($updatesFile, json_encode($updateStatus));
        }

    }

    include __DIR__ . '/publicAssets.php';
}
