<?php

AssetBuilder::set('jquery', [
        '/jquery/jquery-1.12.0.min.js'
], true, 1);

AssetBuilder::set('bootstrap', [
        '/bootstrap/js/bootstrap.min.js',
        '/bootstrap/css/bootstrap.min.css'
], true, 2);

AssetBuilder::set('jquery-ui', [
        '/jquery-ui/jquery-ui.min.js',
        '/jquery-ui/jquery-ui.min.css',
        '/jquery-ui/jquery.ui.touch-punch.min.js'
], false, 5);

AssetBuilder::set('jquery-sortable', [
        '/jquery-ui/jquery-ui.min.js',
        '/jquery-ui/jquery.ui.touch-punch.min.js',
        '/jquery/jquery.mjs.nestedSortable.js',
        '/app/css/sortable.css'
], false, 5);

AssetBuilder::set('cms-main', [
        '/app/css/main.css',
        '/app/js/main.js',
        '/app/js/functions.js'
], true, 100);

AssetBuilder::set('cms-editor', [
        '/jquery-ui/jquery-ui.min.js',
        '/jquery-ui/jquery-ui.min.css',
        '/jquery-ui/jquery.ui.touch-punch.min.js',
        '/jquery/jquery.mousewheel.js',
        '/jquery/fancybox/jquery.fancybox.pack.js',
        '/jquery/fancybox/jquery.fancybox.css',
        '/jquery-ui/jquery-ui-timepicker-addon.js',
        '/jquery/select2/select2.min.js',
        '/jquery/select2/select2.min.css',
        config('coaster::admin.tinymce') == 'compressed' ? '/jquery/tinymce/tinymce.gzip.js' : '/jquery/tinymce/tinymce.min.js',
        '/app/js/functions.js',
        '/app/js/pageInfo.js',
        '/app/js/editor.js'
], false, 5);

AssetBuilder::set('cms-versions', [
        '/app/js/versions.js'
], false, 10);