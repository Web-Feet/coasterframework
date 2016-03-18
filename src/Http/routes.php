<?php

Route::group(['middleware' => ['web', 'guest']], function () {
    // auth routes
    Route::get(config('coaster::admin.url') . '/login', 'CoasterCms\Http\Controllers\Backend\AuthController@getLogin');
    Route::post(config('coaster::admin.url') . '/login', 'CoasterCms\Http\Controllers\Backend\AuthController@postLogin');

    // user account routes
    Route::any(config('coaster::admin.url') . '/forgotten_password', 'CoasterCms\Http\Controllers\Backend\AccountController@forgotten_password');
    Route::any(config('coaster::admin.url') . '/change_password/{all}', 'CoasterCms\Http\Controllers\Backend\AccountController@change_password')->where('all', '.*');
});

Route::group(['middleware' => ['web', 'admin']], function () {
    // admin root
    Route::get(config('coaster::admin.url'), 'CoasterCms\Http\Controllers\Backend\HomeController@getIndex');

    // auth logout
    Route::get(config('coaster::admin.url') . '/logout', 'CoasterCms\Http\Controllers\Backend\AuthController@getLogout');

    // admin controllers
    foreach (CoasterCms\Models\AdminController::preload_all() as $controller) {
        if (!empty($controller->controller)) {
            Route::controller(config('coaster::admin.url') . '/' . $controller->controller, 'CoasterCms\Http\Controllers\Backend\\' . ucwords($controller->controller) . 'Controller');
        }
    }
});

// Files override to enable hosting secure docs
Route::get('uploads/{file_path}', ['middleware' => 'web', function($file_path)
{
  $file_full_path = storage_path().'/uploads/'.$file_path;

  if (file_exists($file_full_path))
  {
      $size = filesize($file_full_path);
      return response()->download($file_full_path, null, ['size' => $size], null);
  }
  else
  {
    $file_full_path = public_path().'/old_upload_structure/'.$file_path;
    $size = filesize($file_full_path);

     return response()->download($file_full_path, null, ['size' => $size], null);
  }

}])->where('file_path', '.*');

Route::group(['middleware' => 'web'], function () {
// catch invalid admin routes
    Route::controller(config('coaster::admin.url'), 'CoasterCms\Http\Controllers\Backend\_Base');

// catch all (rest must be cms pages)
    Route::any('{all}', 'CoasterCms\Http\Controllers\Frontend\PageLoaderController@index')->where('all', '.*');
});
