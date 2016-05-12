<?php

$oldFolder = storage_path('app');
$newFolder = storage_path(config('coaster::site.storage_path'));
$filesToMove = ['install.txt', 'update.log', 'assets.json'];

foreach ($filesToMove as $fileToMove) {
    if (file_exists($oldFolder . '/' . $fileToMove)) {
        File::move($oldFolder . '/' . $fileToMove, $newFolder . '/' . $fileToMove);
    }
}

if (File::exists($newFolder.'/install.txt') && strpos(File::get($newFolder.'/install.txt'), 'complete') !== false) {
    Config::set('coaster::installed', 1);
    try {
        if (!Schema::hasTable('settings')) {
            throw new \PDOException('settings table not found');
        }
    } catch (\PDOException $e) {
        die('Database error, settings table could not be found');
    }
}