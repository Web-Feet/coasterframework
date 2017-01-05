<?php

// Files override to enable hosting secure docs
Route::get('uploads/{file_path}', ['middleware' => ['web', 'auth'], function($file_path)
{
    $file_full_path = storage_path().'/uploads/'.$file_path;

    if (file_exists($file_full_path)) {
        $size = filesize($file_full_path);
        $type = \GuzzleHttp\Psr7\mimetype_from_filename($file_path);
        return response()->download($file_full_path, null, ['size' => $size, 'Content-Type' => $type], null);
    } else {
        return response('upload not found', 404);
    }
}])->where('file_path', '.*');

// catch all (rest must be cms pages)
Route::any('{other}', ['middleware' => ['web', 'coaster.pagebuilder.init'], 'uses' => 'CoasterCms\Http\Controllers\CmsController@generatePage'])->where('other', '.*');
