<?php

Route::any('login', ['uses' => 'AuthController@login', 'as' => 'login']);
Route::any('forgotten-password', ['uses' => 'AccountController@forgottenPassword', 'as' => 'login.password.forgotten']);
Route::any('change-password/{code}', ['uses' => 'AccountController@changePassword', 'as' =>  'login.password.change']);