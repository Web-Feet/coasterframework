<?php

Route::any('install/permissions/{next?}', ['uses' => 'InstallController@checkPermissions', 'as' => 'permissions']);
Route::any('install/database', ['uses' => 'InstallController@setupDatabase', 'as' => 'database']);
Route::any('install/database-save', ['uses' => 'InstallController@saveDatabaseSettings', 'as' => 'databaseSave']);
Route::any('install/database-migrate/{skipEnvCheck?}', ['uses' => 'InstallController@runDatabaseMigrations', 'as' => 'databaseMigrate']);
Route::any('install/admin', ['uses' => 'InstallController@setupAdminUser', 'as' => 'admin']);
Route::any('install/admin-save', ['uses' => 'InstallController@saveAdminUser', 'as' => 'adminSave']);
Route::any('install/theme', ['uses' => 'InstallController@setupTheme', 'as' => 'theme']);
Route::any('install/theme-install', ['uses' => 'InstallController@installTheme', 'as' => 'themeInstall']);

Route::any('{other?}', function() {
    $installRoute = \CoasterCms\Helpers\Cms\Install::getRedirectRoute();
    return redirect()->route($installRoute);
})->where('other', '.*');
