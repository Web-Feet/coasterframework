<?php
// v5.2.26 => v5.2.27

if (config('coaster::installed')) {

    $action = false;
    $galleryBlocks = \CoasterCms\Models\Block::where('type', '=', 'gallery')->get();

    if (!$galleryBlocks->isEmpty()) {
        $galleryStorage = public_path() . '/uploads/system/gallery/';
        foreach ($galleryBlocks as $galleryBlock) {
            $oldGalleryDir = $galleryStorage . $galleryBlock->id;
            $newGalleryDir = $galleryStorage . $galleryBlock->name;
            if (is_dir($oldGalleryDir)) {
                $action = true;
                \CoasterCms\Helpers\File::copyDirectory($oldGalleryDir, $newGalleryDir);
                \CoasterCms\Helpers\File::removeDirectory($oldGalleryDir);
            }
        }
    }

    if ($action) {
        echo "Coaster Framework: Moved gallery files so they are stored by block name\n";
    }

}