<?php

$oldFolder = storage_path('app');
$newFolder = storage_path(config('coaster::site.storage_path'));
$filesToMove = ['install.txt', 'update.log', 'assets.json'];

foreach ($filesToMove as $fileToMove) {
    if (file_exists($oldFolder . '/' . $fileToMove)) {
        File::move($oldFolder . '/' . $fileToMove, $newFolder . '/' . $fileToMove);
    }
}