<?php

$action = false;
$galleryBlocks = \CoasterCms\Models\Block::where('type', '=', 'gallery')->get();

if (!$galleryBlocks->isEmpty()) {
    $galleryStorage = public_path() . '/uploads/system/gallery/';
    foreach ($galleryBlocks as $galleryBlock) {
        $oldGalleryDir = $galleryStorage . $galleryBlock->id;
        $newGalleryDir = $galleryStorage . $galleryBlock->name;
        if (is_dir($oldGalleryDir)) {
            $action = true;
            \CoasterCms\Helpers\Core\File\Directory::copy($oldGalleryDir, $newGalleryDir);
            \CoasterCms\Helpers\Core\File\Directory::remove($oldGalleryDir);
        }
    }
}

if ($action) {
    echo "Coaster Framework: Moved gallery files so they are stored by block name\n";
}

