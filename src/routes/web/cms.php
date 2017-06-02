<?php

// Files override to enable hosting secure docs
Route::get('uploads/{filePath}', ['middleware' => 'coaster.secure_upload', 'uses' => 'CmsController@getSecureUpload'])->where('filePath', '.*');

// catch all (rest must be cms pages)
Route::any('{other}', ['uses' => 'CmsController@generatePage'])->where('other', '.*');