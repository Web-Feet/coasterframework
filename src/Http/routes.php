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

Route::group(['middleware' => 'web'], function () {
// catch invalid admin routes
    Route::controller(config('coaster::admin.url'), 'CoasterCms\Http\Controllers\Backend\_Base');

// catch all (rest must be cms pages)
    Route::any('{all}', 'CoasterCms\Http\Controllers\Frontend\PageLoaderController@index')->where('all', '.*');
});