<?php namespace CoasterCms\Console\Assets;

class JQuery extends AbstractAsset
{

    public static $name = 'jquery';

    public static $version = '1.12.0.1';

    public static $description = 'jQuery';

    public function run()
    {
        $this->downloadFile('https://code.jquery.com/jquery-1.12.0.min.js');
        $this->downloadFile('https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.js');
        $this->downloadZip(
            'https://github.com/ilikenwf/nestedSortable/archive/master.zip',
            ['nestedSortable-master/jquery.mjs.nestedSortable.js' => 'jquery.mjs.nestedSortable.js']
        );
        $this->downloadZip(
            'https://github.com/fancyapps/fancyBox/archive/v2.1.5.zip',
            ['fancybox-2.1.5/source' => 'fancybox']
        );
        $vendorGallery = base_path('vendor/blueimp/jquery-file-upload');
        foreach (['cors', 'css', 'img', 'js'] as $dir) {
            $this->copyFrom($vendorGallery.'/'.$dir, 'gallery-upload/'.$dir);
        }
        $this->downloadZip(
            'https://github.com/blueimp/Gallery/archive/2.16.0.zip',
            ['Gallery-2.16.0/js/jquery.blueimp-gallery.min.js' => 'gallery-upload/js/external/jquery.blueimp-gallery.min.js']
        );
        $this->downloadZip(
            'https://github.com/blueimp/JavaScript-Canvas-to-Blob/archive/2.2.0.zip',
            ['JavaScript-Canvas-to-Blob-2.2.0/js/canvas-to-blob.min.js' => 'gallery-upload/js/external/canvas-to-blob.min.js']
        );
        $this->downloadZip(
            'https://github.com/blueimp/JavaScript-Load-Image/archive/1.14.0.zip',
            ['JavaScript-Load-Image-1.14.0/js/load-image.all.min.js' => 'gallery-upload/js/external/load-image.all.min.js']
        );
        $this->downloadZip(
            'https://github.com/blueimp/JavaScript-Templates/archive/2.5.5.zip',
            ['JavaScript-Templates-2.5.5/js/tmpl.min.js' => 'gallery-upload/js/external/tmpl.min.js']
        );
        $this->downloadZip(
            'https://github.com/select2/select2/archive/4.0.2.zip',
            [
                'select2-4.0.2/dist/css/select2.min.css' => 'select2/select2.min.css',
                'select2-4.0.2/dist/js/select2.min.js' => 'select2/select2.min.js'
            ]
        );
        $this->downloadZip(
            'https://github.com/tinymce/tinymce-dist/archive/4.7.11.zip',
            ['tinymce-dist-4.7.11' => 'tinymce']
        );
        $this->downloadZip(
            'https://github.com/tinymce/tinymce_compressor/archive/4.0.0.zip',
            ['tinymce_compressor-4.0.0/tinymce.gzip.js' => 'tinymce/tinymce.gzip.js']
        );
        $this->copyFrom('');
    }

}