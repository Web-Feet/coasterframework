<?php namespace CoasterCms\Console\Assets;

use File;

class StorageFileUpdates extends AbstractAsset
{

    public static $name = 'storage-update';

    public static $description = 'Storage File Checks';

    public static $version = '-';

    public function run()
    {
        $filesToMove = ['install.txt', 'assets.json', 'update.log'];
        $filesToRemove = ['update.log', 'updates.json'];

        // changed location as of v5.2.39
        $oldFolder = storage_path('app');
        $newFolder = storage_path(config('coaster::site.storage_path'));
        foreach ($filesToMove as $fileToMove) {
            if (file_exists($oldFolder . '/' . $fileToMove)) {
                File::move($oldFolder . '/' . $fileToMove, $newFolder . '/' . $fileToMove);
            }
        }

        // updates file removed in v5.4.13
        foreach ($filesToRemove as $fileToRemove) {
            if (file_exists($newFolder . '/' . $fileToRemove)) {
                unlink($newFolder . '/' . $fileToRemove);
            }
        }
    }

}
