<?php

$coasterPublicFolder = public_path() . config('coaster::admin.public');
$assetsFile = storage_path(config('coaster::site.storage_path')).'/assets.json';
if (file_exists($assetsFile)) {
    $assetsVersions = file_get_contents($assetsFile);
    $assetsVersions = json_decode($assetsVersions, true);
} else {
    $assetsVersions = [];
}
$guzzleClient = new \GuzzleHttp\Client;

// force overwrite option
$force = (bool) (!empty($argv[1]) && $argv[1] == '--force' ? true : false);
if ($force && !empty($argv[2]) ) {
    $force = false;
    if (!empty($assetsVersions[$argv[2]])) {
        $assetsVersions[$argv[2]] = 0;
    }
}

/*
 * App Folder
 */

if (empty($assetsVersions['app']) || $force || version_compare($assetsVersions['app'], config('coaster::site.version'), '<')) {

    echo "Coaster Framework: Updating core coaster app assets .";

    if (!file_exists($coasterPublicFolder . '/app/')) {
        mkdir($coasterPublicFolder . '/app/', 0777, true);
    }
    \CoasterCms\Helpers\Cms\File\Directory::copy(realpath(__DIR__.'/../public/app') , $coasterPublicFolder . '/app/');
    echo ".";

    $assetsVersions['app'] = config('coaster::site.version');
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}

 /*
  * ACE (HTML / Code Editor)
  */

if (empty($assetsVersions['ace']) || $force || version_compare($assetsVersions['ace'], '1.2.5', '<')) {

    echo "Coaster Framework: Updating ACE HTML/CSS/Code Editor .";

    $assetsVersions['ace'] = '1.2.5';

    $releaseFileName = 'v'.$assetsVersions['ace'].'.zip';
    $zipPath = public_path('coaster/ace-'.$releaseFileName);
    $response = $guzzleClient->request('GET', 'https://github.com/ajaxorg/ace-builds/archive/'.$releaseFileName, [
        'sink' => $zipPath
    ])->getBody()->close();
    echo ".";

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($zipPath);
    $zip->extractDir('ace-builds-'.$assetsVersions['ace'].'/src-min', public_path('coaster/ace'));
    $zip->close();
    unlink($zipPath);

    // Copy html_blade syntax highlighter
    copy(realpath(__DIR__.'/../public/ace/mode-html_blade.js'), $coasterPublicFolder . '/ace/mode-html_blade.js');
    echo ".";

    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}


/*
 * Bootstrap
 */
if (empty($assetsVersions['bootstrap']) || $force || version_compare($assetsVersions['bootstrap'], '3.3.6', '<')) {

    echo "Coaster Framework: Updating twitter bootstrap .";

    $bootstrapZip = public_path('coaster/bootstrap-3.3.6-dist.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/twbs/bootstrap/releases/download/v3.3.6/bootstrap-3.3.6-dist.zip', [
        'sink' => $bootstrapZip
    ])->getBody()->close();
    echo ".";

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($bootstrapZip);
    $zip->extractDir('bootstrap-3.3.6-dist', public_path('coaster/bootstrap'));
    $zip->close();
    unlink($bootstrapZip);

    $assetsVersions['bootstrap'] = '3.3.6';
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}

/*
 * File Manager
 */

if (empty($assetsVersions['filemanager']) || $force || version_compare($assetsVersions['filemanager'], 'v9.10.1', '<')) {

    echo "Coaster Framework: Updating responsive file manager .";

    $responsiveFileManagerLocation = public_path('coaster/filemanager');
    $responsiveFileManagerZip = public_path('coaster/responsive_filemanager.zip');
    $client = new \GuzzleHttp\Client;
    $response = $guzzleClient->request('GET', 'https://github.com/trippo/ResponsiveFilemanager/releases/download/v9.10.1/responsive_filemanager.zip', [
        'sink' => $responsiveFileManagerZip
    ])->getBody()->close();
    echo ".";

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($responsiveFileManagerZip);
    $zip->extractDir('filemanager', $responsiveFileManagerLocation);
    $zip->extractDir('tinymce/plugins/responsivefilemanager', public_path('coaster/jquery/tinymce/plugins/responsivefilemanager'));
    $zip->close();
    unlink($responsiveFileManagerZip);
    echo ".";

    \CoasterCms\Helpers\Cms\File\File::insertAtLine($responsiveFileManagerLocation . '/config/config.php', [
        362 => [
            'require __DIR__ .\'/../../../../vendor/web-feet/coasterframework/hooks/laravel.php\';',
            '\CoasterCms\Helpers\Admin\FileManager::accessCheck();',
            '\CoasterCms\Helpers\Admin\FileManager::setConfig($config, []);',
            ''
        ]
    ]);
    \CoasterCms\Helpers\Cms\File\File::insertAtLine($responsiveFileManagerLocation . '/dialog.php', [
        84 => [
            '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($subdir);'
        ]
    ]);
    \CoasterCms\Helpers\Cms\File\File::insertAtLine($responsiveFileManagerLocation . '/execute.php', [
        33 => [
            '\CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'path\']);'
        ]
    ]);
    \CoasterCms\Helpers\Cms\File\File::insertAtLine($responsiveFileManagerLocation . '/upload.php', [
        19 => [
            '   \CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'path\']);'
        ],
        24 => [
            '   \CoasterCms\Helpers\Admin\FileManager::setSecureUpload($_POST[\'fldr\']);'
        ]
    ]);

    // remove conflicting response class
    unlink($responsiveFileManagerLocation . '/include/Response.php');
    $clearResponseFile = fopen($responsiveFileManagerLocation . '/include/Response.php', 'w');
    fclose($clearResponseFile);

    // trans/endsWith func conflict name change
    \CoasterCms\Helpers\Cms\File\File::replaceString($responsiveFileManagerLocation . '/include/utils.php', 'endsWith(', 'endsWithfm(');
    \CoasterCms\Helpers\Cms\File\File::replaceString($responsiveFileManagerLocation . '/include/utils.php', '\'trans\'', '\'transfm\'');
    $files = [
        '/ajax_calls.php',
        '/dialog.php',
        '/execute.php',
        '/force_download.php',
        '/upload.php',
        '/include/utils.php'
    ];
    foreach ($files as $file) {
        \CoasterCms\Helpers\Cms\File\File::replaceString($responsiveFileManagerLocation . $file, 'trans(', 'transfm(');
    }

    // permissions fix
    \CoasterCms\Helpers\Cms\File\File::replaceString($responsiveFileManagerLocation . '/include/utils.php', 'umask(0)', 'umask()');
    \CoasterCms\Helpers\Cms\File\File::replaceString($responsiveFileManagerLocation . '/include/utils.php', '0766', 'CoasterCms\Helpers\Admin\FileManager::createDirPermissions()');

    $assetsVersions['filemanager'] = 'v9.10.1';
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}

/*
 * jQuery
 */

if (empty($assetsVersions['jquery']) || $force || version_compare($assetsVersions['jquery'], '1.12.0', '<')) {

    echo "Coaster Framework: Updating jQuery .";

    if (!file_exists($coasterPublicFolder . '/jquery/')) {
        mkdir($coasterPublicFolder . '/jquery/', 0777, true);
    }

    $response = $guzzleClient->request('GET', 'https://code.jquery.com/jquery-1.12.0.min.js', [
        'sink' => public_path('coaster/jquery/jquery-1.12.0.min.js')
    ])->getBody()->close();
    echo ".";

    $response = $guzzleClient->request('GET', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.js', [
        'sink' => public_path('coaster/jquery/jquery.mousewheel.js')
    ])->getBody()->close();
    echo ".";

    $nestedSortableZip = public_path('coaster/jquery/nestedSortable-master.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/ilikenwf/nestedSortable/archive/master.zip', [
        'sink' => $nestedSortableZip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($nestedSortableZip);
    $zip->extractFile('nestedSortable-master/jquery.mjs.nestedSortable.js', public_path('coaster/jquery/jquery.mjs.nestedSortable.js'));
    $zip->close();
    unlink($nestedSortableZip);
    echo ".";

    $fancyBoxZip = public_path('coaster/jquery/fancyBox-2.1.5.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/fancyapps/fancyBox/archive/v2.1.5.zip', [
        'sink' => $fancyBoxZip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($fancyBoxZip);
    $zip->extractDir('fancybox-2.1.5/source', public_path('coaster/jquery/fancybox'));
    $zip->close();
    unlink($fancyBoxZip);
    echo ".";

    $jQueryFileUploadDir = base_path('vendor/blueimp/jquery-file-upload');
    $jQueryFileUploadPublicDir = public_path('coaster/jquery/gallery-upload');
    \CoasterCms\Helpers\Cms\File\Directory::copy($jQueryFileUploadDir . '/cors', $jQueryFileUploadPublicDir . '/cors');
    \CoasterCms\Helpers\Cms\File\Directory::copy($jQueryFileUploadDir . '/css', $jQueryFileUploadPublicDir . '/css');
    \CoasterCms\Helpers\Cms\File\Directory::copy($jQueryFileUploadDir . '/img', $jQueryFileUploadPublicDir . '/img');
    \CoasterCms\Helpers\Cms\File\Directory::copy($jQueryFileUploadDir . '/js', $jQueryFileUploadPublicDir . '/js');
    echo ".";

    $jQueryFileUploadExternal = $jQueryFileUploadPublicDir . '/Gallery-2.16.0.zip';
    $response = $guzzleClient->request('GET', 'https://github.com/blueimp/Gallery/archive/2.16.0.zip', [
        'sink' => $jQueryFileUploadExternal
    ])->getBody()->close();
    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($jQueryFileUploadExternal);
    $zip->extractFile('Gallery-2.16.0/js/jquery.blueimp-gallery.min.js', $jQueryFileUploadPublicDir . '/js/external/jquery.blueimp-gallery.min.js');
    $zip->close();
    unlink($jQueryFileUploadExternal);
    echo ".";

    $jQueryFileUploadExternal = $jQueryFileUploadPublicDir . '/JavaScript-Canvas-to-Blob-2.2.0.zip';
    $response = $guzzleClient->request('GET', 'https://github.com/blueimp/JavaScript-Canvas-to-Blob/archive/2.2.0.zip', [
        'sink' => $jQueryFileUploadExternal
    ])->getBody()->close();
    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($jQueryFileUploadExternal);
    $zip->extractFile('JavaScript-Canvas-to-Blob-2.2.0/js/canvas-to-blob.min.js', $jQueryFileUploadPublicDir . '/js/external/canvas-to-blob.min.js');
    $zip->close();
    unlink($jQueryFileUploadExternal);
    echo ".";

    $jQueryFileUploadExternal = $jQueryFileUploadPublicDir . '/JavaScript-Load-Image-1.14.0.zip';
    $response = $guzzleClient->request('GET', 'https://github.com/blueimp/JavaScript-Load-Image/archive/1.14.0.zip', [
        'sink' => $jQueryFileUploadExternal
    ])->getBody()->close();
    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($jQueryFileUploadExternal);
    $zip->extractFile('JavaScript-Load-Image-1.14.0/js/load-image.all.min.js', $jQueryFileUploadPublicDir . '/js/external/load-image.all.min.js');
    $zip->close();
    unlink($jQueryFileUploadExternal);
    echo ".";

    $jQueryFileUploadExternal = $jQueryFileUploadPublicDir . '/JavaScript-Templates-2.5.5.zip';
    $response = $guzzleClient->request('GET', 'https://github.com/blueimp/JavaScript-Templates/archive/2.5.5.zip', [
        'sink' => $jQueryFileUploadExternal
    ])->getBody()->close();
    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($jQueryFileUploadExternal);
    $zip->extractFile('JavaScript-Templates-2.5.5/js/tmpl.min.js', $jQueryFileUploadPublicDir . '/js/external/tmpl.min.js');
    $zip->close();
    unlink($jQueryFileUploadExternal);
    echo ".";

    $select2Zip = public_path('coaster/jquery/select2-4.0.2.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/select2/select2/archive/4.0.2.zip', [
        'sink' => $select2Zip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($select2Zip);
    $zip->extractFile('select2-4.0.2/dist/css/select2.min.css', public_path('coaster/jquery/select2/select2.min.css'));
    $zip->extractFile('select2-4.0.2/dist/js/select2.min.js', public_path('coaster/jquery/select2/select2.min.js'));
    $zip->close();
    unlink($select2Zip);

    $tinyMceZip = public_path('coaster/jquery/tinymce-dist-4.3.3.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/tinymce/tinymce-dist/archive/4.3.3.zip', [
        'sink' => $tinyMceZip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($tinyMceZip);
    $zip->extractDir('tinymce-dist-4.3.3', public_path('coaster/jquery/tinymce'));
    $zip->close();
    unlink($tinyMceZip);

    $tinyCompressor2Zip = public_path('coaster/jquery/tinymce_compressor-4.0.0.zip');
    $response = $guzzleClient->request('GET', 'https://github.com/tinymce/tinymce_compressor/archive/4.0.0.zip', [
        'sink' => $tinyCompressor2Zip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($tinyCompressor2Zip);
    $zip->extractFile('tinymce_compressor-4.0.0/tinymce.gzip.js', public_path('coaster/jquery/tinymce/tinymce.gzip.js'));
    $zip->close();
    unlink($tinyCompressor2Zip);

    // Copy php compressor
    copy(realpath(__DIR__.'/../public/jquery/tinymce/tinymce.gzip.php'), $coasterPublicFolder . '/jquery/tinymce/tinymce.gzip.php');
    echo ".";

    $assetsVersions['jquery'] = '1.12.0';
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}

/*
 * jQuery UI
 */

if (empty($assetsVersions['jquery-ui']) || $force || version_compare($assetsVersions['jquery-ui'], '1.11.3', '<')) {

    echo "Coaster Framework: Updating jQuery-ui .";

    $jQueryUIZip = public_path('coaster/jquery-ui-1.11.4.custom.zip');
    $response = $guzzleClient->request('POST', 'http://download.jqueryui.com/download', [
        'form_params' => [
            'theme' => 'ffDefault=Trebuchet%20MS%2CTahoma%2CVerdana%2CArial%2Csans-serif&fsDefault=1.1em&fwDefault=bold&cornerRadius=2px&bgColorHeader=%23eb5b4f&bgTextureHeader=flat&borderColorHeader=%23eb5b4f&fcHeader=%23fff&iconColorHeader=%23ffffff&bgColorContent=%23fff&bgTextureContent=highlight_soft&borderColorContent=%23dddddd&fcContent=%23333333&iconColorContent=%23222222&bgColorDefault=%23fff&bgTextureDefault=glass&borderColorDefault=%23ccc&fcDefault=%23333&iconColorDefault=%23333&bgColorHover=%2300184a&bgTextureHover=inset_soft&borderColorHover=%2300184a&fcHover=%23fff&iconColorHover=%23fff&bgColorActive=%23ffffff&bgTextureActive=glass&borderColorActive=%23eb5b4f&fcActive=%23eb5b4f&iconColorActive=%23eb5b4f&bgColorHighlight=%2300184a&bgTextureHighlight=highlight_soft&borderColorHighlight=%2300184a&fcHighlight=%23fff&iconColorHighlight=%23fff&bgColorError=%23b81900&bgTextureError=diagonals_thick&borderColorError=%23cd0a0a&fcError=%23ffffff&iconColorError=%23ffd27a&bgColorOverlay=%23eb5b4f&bgTextureOverlay=flat&bgImgOpacityOverlay=0&opacityOverlay=80&bgColorShadow=%23000000&bgTextureShadow=flat&bgImgOpacityShadow=10&opacityShadow=1&thicknessShadow=20px&offsetTopShadow=5px&offsetLeftShadow=5px&cornerRadiusShadow=5px&bgImgOpacityHeader=35&bgImgOpacityContent=0&bgImgOpacityDefault=0&bgImgOpacityHover=20&bgImgOpacityActive=65&bgImgOpacityHighlight=20&bgImgOpacityError=18',
            'core' => 'on',
            'widget' => 'on',
            'mouse' => 'on',
            'position' => 'on',
            'draggable' => 'on',
            'droppable' => 'on',
            'resizable' => 'on',
            'selectable' => 'on',
            'sortable' => 'on',
            'accordion' => 'on',
            'autocomplete' => 'on',
            'button' => 'on',
            'datepicker' => 'on',
            'dialog' => 'on',
            'menu' => 'on',
            'progressbar' => 'on',
            'selectmenu' => 'on',
            'slider' => 'on',
            'spinner' => 'on',
            'effect' => 'on',
            'effect-blind' => 'on',
            'effect-bounce' => 'on',
            'effect-clip' => 'on',
            'effect-drop' => 'on',
            'effect-explode' => 'on',
            'effect-fade' => 'on',
            'effect-fold' => 'on',
            'effect-highlight' => 'on',
            'effect-puff' => 'on',
            'effect-pulsate' => 'on',
            'effect-scale' => 'on',
            'effect-shake' => 'on',
            'effect-size' => 'on',
            'effect-slide' => 'on',
            'effect-transfer' => 'on',
            'version' => '1.11.4'
        ],
        'sink' => $jQueryUIZip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($jQueryUIZip);
    $zip->extractDir('jquery-ui-1.11.4.custom', public_path('coaster/jquery-ui'));
    $zip->close();
    unlink($jQueryUIZip);
    echo ".";

    $timePickerZip = public_path('coaster/jquery-ui/jQuery-Timepicker-Addon-1.4.zip');
    $timePickerFile = public_path('coaster/jquery-ui/jquery-ui-timepicker-addon.js');
    $response = $guzzleClient->request('GET', 'https://github.com/trentrichardson/jQuery-Timepicker-Addon/archive/v1.4.zip', [
        'sink' => $timePickerZip
    ])->getBody()->close();

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($timePickerZip);
    $zip->extractFile('jQuery-Timepicker-Addon-1.4/dist/jquery-ui-timepicker-addon.js', $timePickerFile);
    $zip->close();
    unlink($timePickerZip);
    \CoasterCms\Helpers\Cms\File\File::replaceString($timePickerFile, 'formattedDateTime += this._defaults.separator + this.formattedTime + this._defaults.timeSuffix;', 'formattedDateTime = this.formattedTime + this._defaults.timeSuffix + this._defaults.separator + formattedDateTime;');
    echo ".";

    $response = $guzzleClient->request('GET', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.2/jquery.ui.touch-punch.min.js', [
        'sink' => public_path('coaster/jquery-ui/jquery.ui.touch-punch.min.js')
    ])->getBody()->close();

    $assetsVersions['jquery-ui'] = '1.11.4';
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}

/*
 * Securimage
 */

if (empty($assetsVersions['securimage']) || $force || version_compare($assetsVersions['securimage'], '3.6.3', '<')) {

    echo "Coaster Framework: Updating securimage captcha .";

    $secureImageZip = public_path('coaster/securimage-3.6.3.zip');
    $client = new \GuzzleHttp\Client;
    $response = $guzzleClient->request('GET', 'https://github.com/dapphp/securimage/archive/3.6.3.zip', [
        'sink' => $secureImageZip
    ])->getBody()->close();
    echo ".";

    $zip = new \CoasterCms\Helpers\Cms\File\Zip;
    $zip->open($secureImageZip);
    $zip->extractDir('securimage-3.6.3', public_path('coaster/securimage'));
    $zip->close();
    unlink($secureImageZip);

    $assetsVersions['securimage'] = '3.6.3';
    file_put_contents($assetsFile, json_encode($assetsVersions));

    echo " done\n";
}
