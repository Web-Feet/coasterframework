<?php

$routeNamePrefix = 'coaster.install.'; 
$installController = 'CoasterCms\Http\Controllers\InstallController';

Route::any('install/permissions/{next?}', ['uses' => $installController . '@checkPermissions', 'as' => $routeNamePrefix . 'permissions']);
Route::any('install/database', ['uses' => $installController . '@setupDatabase', 'as' => $routeNamePrefix . 'database']);
Route::any('install/database-save', ['uses' => $installController . '@saveDatabaseSettings', 'as' => $routeNamePrefix . 'databaseSave']);
Route::any('install/database-migrate', ['uses' => $installController . '@runDatabaseMigrations', 'as' => $routeNamePrefix . 'databaseMigrate']);
Route::any('install/admin', ['uses' => $installController . '@setupAdminUser', 'as' => $routeNamePrefix . 'admin']);
Route::any('install/admin-save', ['uses' => $installController . '@saveAdminUser', 'as' => $routeNamePrefix . 'adminSave']);
Route::any('install/theme', ['uses' => $installController . '@setupTheme', 'as' => $routeNamePrefix . 'theme']);
Route::any('install/theme-install', ['uses' => $installController . '@installTheme', 'as' => $routeNamePrefix . 'themeInstall']);

Route::any('{other?}', ['uses' => function($other) {
    $installRoute = \CoasterCms\Helpers\Cms\Install::getRedirectRoute();
    return redirect()->route($installRoute);
}, 'as' => 'coaster.admin'])->where('other', '.*');
