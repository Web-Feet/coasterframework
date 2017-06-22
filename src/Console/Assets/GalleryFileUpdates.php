<?php namespace CoasterCms\Console\Assets;

use CoasterCms\Models\Block;
use CoasterCms\Helpers\Cms\File\Directory;
use File;

class GalleryFileUpdates extends AbstractAsset
{

    public static $name = 'gallery-update';

    public static $description = 'Gallery Upload File Checks';

    public static $version = '-';

    public function run()
    {
        $action = false;
        $galleryBlocks = Block::where('type', '=', 'gallery')->get();

        // location change to name based as of v5.2.27
        if (!$galleryBlocks->isEmpty()) {
            $galleryStorage = public_path() . '/uploads/system/gallery/';
            foreach ($galleryBlocks as $galleryBlock) {
                $oldGalleryDir = $galleryStorage . $galleryBlock->id;
                $newGalleryDir = $galleryStorage . $galleryBlock->name;
                if (is_dir($oldGalleryDir)) {
                    $action = true;
                    Directory::copy($oldGalleryDir, $newGalleryDir);
                    Directory::remove($oldGalleryDir);
                }
            }
        }

        if ($action) {
            $this->_report('Moved gallery files so they are now stored by block name rather than id.');
        }
    }

}