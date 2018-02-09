<?php

// default home route
Route::get('', ['uses' => 'AdminControllers\HomeController@getIndex']);

// catch invalid admin routes
Route::any('{other}', ['uses' => 'AdminController@catchAll', 'as' => '.{other}'])->where('other', '.*');
