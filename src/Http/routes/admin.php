<?php

$routeNamePrefix = 'coaster.admin.';
$admin = config('coaster::admin.url') . '/';
$adminController = 'CoasterCms\Http\Controllers\AdminControllers\\';

Route::group(['middleware' => ['web', 'coaster.guest']], function () use($admin, $adminController, $routeNamePrefix) {

    Route::any($admin . 'login', ['uses' => $adminController . 'AuthController@login', 'as' => $routeNamePrefix . 'login']);
    Route::any($admin . 'forgotten_password', ['uses' => $adminController . 'AccountController@forgottenPassword', 'as' => $routeNamePrefix . 'login.password.forgotten']);
    Route::any($admin . 'change_password/{code}', ['uses' => $adminController . 'AccountController@changePassword', 'as' => $routeNamePrefix . 'login.password.change']);

});

Route::group(['middleware' => ['web', 'coaster.admin']], function () use($admin, $adminController, $routeNamePrefix) {

    Route::get($admin, ['uses' => $adminController . 'HomeController@getIndex', 'as' => rtrim($routeNamePrefix, '.')]);

    Route::get($admin . 'logout', ['uses' => $adminController . 'AuthController@logout', 'as' => $routeNamePrefix . 'logout']);

    Route::get($admin . 'home', ['uses' => $adminController . 'HomeController@getIndex', 'as' => $routeNamePrefix . 'home']);
    Route::get($admin . 'home/logs', ['uses' => $adminController . 'HomeController@getLogs', 'as' => $routeNamePrefix . 'home.logs']);
    Route::get($admin . 'home/requests', ['uses' => $adminController . 'HomeController@getRequests', 'as' => $routeNamePrefix . 'home.requests']);
    Route::get($admin . 'home/your-requests', ['uses' => $adminController . 'HomeController@getYourRequests', 'as' => $routeNamePrefix . 'home.your-requests']);

    Route::get($admin . 'account', ['uses' => $adminController . 'AccountController@getIndex', 'as' => $routeNamePrefix . '']);
    Route::get($admin . 'account/password', ['uses' => $adminController . 'AccountController@getPassword', 'as' => $routeNamePrefix . 'account.password']);
    Route::post($admin . 'account/password', ['uses' => $adminController . 'AccountController@postPassword']);
    Route::get($admin . 'account/blog', ['uses' => $adminController . 'AccountController@getBlog', 'as' => $routeNamePrefix . 'account.blog']);
    Route::post($admin . 'account/blog', ['uses' => $adminController . 'AccountController@postBlog']);
    Route::get($admin . 'account/language', ['uses' => $adminController . 'AccountController@getLanguage', 'as' => $routeNamePrefix . 'account.language']);
    Route::post($admin . 'account/language', ['uses' => $adminController . 'AccountController@postLanguage']);
    Route::post($admin . 'account/page-state', ['uses' => $adminController . 'AccountController@postPageState', 'as' => $routeNamePrefix . 'account.page-state']);

    Route::get($admin . 'pages', ['uses' => $adminController . 'PagesController@get_index', 'as' => '']);
    Route::get($admin . 'pages/add/{page_id?}', ['uses' => $adminController . 'PagesController@get_add', 'as' => '']);
    Route::post($admin . 'pages/add/{page_id?}', ['uses' => $adminController . 'PagesController@post_add', 'as' => '']);
    Route::get($admin . 'pages/edit/{page_id}/{version?}', ['uses' => $adminController . 'PagesController@get_edit', 'as' => ''])->where(array ( 'page_id' => '\w+', 'version' => '\w+', ));
    Route::post($admin . 'pages/edit/{page_id}', ['uses' => $adminController . 'PagesController@post_edit', 'as' => '']);
    Route::post($admin . 'pages/sort', ['uses' => $adminController . 'PagesController@post_sort', 'as' => '']);
    Route::post($admin . 'pages/delete/{page_id}', ['uses' => $adminController . 'PagesController@post_delete', 'as' => '']);
    Route::post($admin . 'pages/versions/{page_id}', ['uses' => $adminController . 'PagesController@post_versions', 'as' => '']);
    Route::post($admin . 'pages/version-schedule/{pageId}', ['uses' => $adminController . 'PagesController@post_version_schedule', 'as' => '']);
    Route::post($admin . 'pages/version-rename/{page_id}', ['uses' => $adminController . 'PagesController@post_version_rename', 'as' => '']);
    Route::post($admin . 'pages/version-publish/{page_id}', ['uses' => $adminController . 'PagesController@post_version_publish', 'as' => '']);
    Route::post($admin . 'pages/requests/{page_id}', ['uses' => $adminController . 'PagesController@post_requests', 'as' => '']);
    Route::post($admin . 'pages/request-publish/{page_id}', ['uses' => $adminController . 'PagesController@post_request_publish', 'as' => '']);
    Route::post($admin . 'pages/request-publish-action/{page_id}', ['uses' => $adminController . 'PagesController@post_request_publish_action', 'as' => '']);
    Route::get($admin . 'pages/tinymce-page-list', ['uses' => $adminController . 'PagesController@getTinymcePageList', 'as' => '']);
    Route::get($admin . 'pages/middleware', ['uses' => $adminController . 'PagesController@getMiddleware', 'as' => '']);
    Route::get($admin . 'groups/pages/{group_id}', ['uses' => $adminController . 'GroupsController@get_pages', 'as' => '']);

    Route::get($admin . 'menus', ['uses' => $adminController . 'MenusController@get_index', 'as' => '']);
    Route::post($admin . 'menus/add', ['uses' => $adminController . 'MenusController@post_add', 'as' => '']);
    Route::post($admin . 'menus/delete/{item_id}', ['uses' => $adminController . 'MenusController@post_delete', 'as' => '']);
    Route::post($admin . 'menus/sort', ['uses' => $adminController . 'MenusController@post_sort', 'as' => '']);
    Route::post($admin . 'menus/get-levels', ['uses' => $adminController . 'MenusController@post_get_levels', 'as' => '']);
    Route::post($admin . 'menus/save-levels', ['uses' => $adminController . 'MenusController@post_save_levels', 'as' => '']);
    Route::post($admin . 'menus/rename', ['uses' => $adminController . 'MenusController@post_rename', 'as' => '']);

    Route::get($admin . 'blocks', ['uses' => $adminController . 'BlocksController@get_index', 'as' => '']);
    Route::post($admin . 'blocks', ['uses' => $adminController . 'BlocksController@post_index', 'as' => '']);

    Route::get($admin . 'filemanager', ['uses' => $adminController . 'FilemanagerController@get_index', 'as' => '']);

    Route::get($admin . 'redirects', ['uses' => $adminController . 'RedirectsController@get_index', 'as' => '']);
    Route::post($admin . 'redirects', ['uses' => $adminController . 'RedirectsController@post_index', 'as' => '']);
    Route::get($admin . 'redirects/url-decode', ['uses' => $adminController . 'RedirectsController@getUrlDecode', 'as' => '']);
    Route::post($admin . 'redirects/edit', ['uses' => $adminController . 'RedirectsController@postEdit', 'as' => '']);
    Route::get($admin . 'redirects/import', ['uses' => $adminController . 'RedirectsController@get_import', 'as' => '']);

    Route::post($admin . 'system', ['uses' => $adminController . 'SystemController@post_index', 'as' => '']);
    Route::get($admin . 'system', ['uses' => $adminController . 'SystemController@get_index', 'as' => '']);
    Route::get($admin . 'system/wp-login', ['uses' => $adminController . 'SystemController@get_wp_login', 'as' => '']);
    Route::get($admin . 'system/search', ['uses' => $adminController . 'SystemController@get_search', 'as' => '']);
    Route::get($admin . 'system/validate-db/{fix?}', ['uses' => $adminController . 'SystemController@get_validate_db', 'as' => '']);
    Route::get($admin . 'system/upgrade/{update?}', ['uses' => $adminController . 'SystemController@getUpgrade', 'as' => '']);
    Route::post($admin . 'system/keys/{key?}', ['uses' => $adminController . 'SystemController@post_keys', 'as' => '']);

    Route::get($admin . 'users', ['uses' => $adminController . 'UsersController@get_index', 'as' => '']);
    Route::post($admin . 'users/edit/{user_id?}/{action?}', ['uses' => $adminController . 'UsersController@post_edit', 'as' => ''])->where(array ( 'user_id' => '\w+', 'action' => '\w+', ));
    Route::get($admin . 'users/edit/{user_id?}/{action?}', ['uses' => $adminController . 'UsersController@get_edit', 'as' => ''])->where(array ( 'user_id' => '\w+', 'action' => '\w+', ));
    Route::get($admin . 'users/add', ['uses' => $adminController . 'UsersController@get_add', 'as' => '']);
    Route::post($admin . 'users/add', ['uses' => $adminController . 'UsersController@post_add', 'as' => '']);
    Route::post($admin . 'users/delete/{user_id?}', ['uses' => $adminController . 'UsersController@post_delete', 'as' => '']);

    Route::get($admin . 'roles', ['uses' => $adminController . 'RolesController@get_index', 'as' => '']);
    Route::post($admin . 'roles/actions/{role_id?}', ['uses' => $adminController . 'RolesController@post_actions', 'as' => '']);
    Route::post($admin . 'roles/add', ['uses' => $adminController . 'RolesController@post_add', 'as' => '']);
    Route::post($admin . 'roles/edit', ['uses' => $adminController . 'RolesController@post_edit', 'as' => '']);
    Route::post($admin . 'roles/delete', ['uses' => $adminController . 'RolesController@post_delete', 'as' => '']);
    Route::get($admin . 'roles/pages/{role_id}', ['uses' => $adminController . 'RolesController@get_pages', 'as' => '']);
    Route::post($admin . 'roles/pages/{role_id}', ['uses' => $adminController . 'RolesController@post_pages', 'as' => '']);

    Route::post($admin . 'backups/undo', ['uses' => $adminController . 'BackupsController@post_undo', 'as' => '']);
    Route::get($admin . 'backups/middleware', ['uses' => $adminController . 'BackupsController@getMiddleware', 'as' => '']);
    Route::post($admin . 'repeaters', ['uses' => $adminController . 'RepeatersController@post_index', 'as' => '']);

    Route::get($admin . 'gallery/list/{page_id?}', ['uses' => $adminController . 'GalleryController@get_list', 'as' => '']);
    Route::get($admin . 'gallery/edit/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@getEdit', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));
    Route::get($admin . 'gallery/update/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@get_update', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));
    Route::post($admin . 'gallery/caption/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@post_caption', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));
    Route::post($admin . 'gallery/sort/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@post_sort', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));;
    Route::post($admin . 'gallery/update/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@post_update', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));
    Route::delete($admin . 'gallery/update/{page_id?}/{block_id?}', ['uses' => $adminController . 'GalleryController@delete_update', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));

    Route::get($admin . 'forms/list/{page_id?}', ['uses' => $adminController . 'FormsController@get_list', 'as' => '']);
    Route::get($admin . 'forms/submissions/{page_id?}/{block_id?}', ['uses' => $adminController . 'FormsController@get_submissions', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));
    Route::get($admin . 'forms/csv/{page_id?}/{block_id?}', ['uses' => $adminController . 'FormsController@get_csv', 'as' => ''])->where(array ( 'page_id' => '\w+', 'block_id' => '\w+', ));;

    Route::get($admin . 'themes', ['uses' => $adminController . 'ThemesController@getIndex', 'as' => '']);
    Route::get($admin . 'themes/list', ['uses' => $adminController . 'ThemesController@getList', 'as' => '']);
    Route::post($admin . 'themes/manage', ['uses' => $adminController . 'ThemesController@postManage', 'as' => '']);
    Route::get($admin . 'themes/export/{themeId}/{withPageData?}', ['uses' => $adminController . 'ThemesController@getExport', 'as' => ''])->where(array ( 'themeId' => '\w+', 'withPageData' => '\w+', ));
    Route::get($admin . 'themes/beacons', ['uses' => $adminController . 'ThemesController@getBeacons', 'as' => '']);
    Route::post($admin . 'themes/beacons', ['uses' => $adminController . 'ThemesController@postBeacons', 'as' => '']);
    Route::get($admin . 'themes/update/{themeId}', ['uses' => $adminController . 'ThemesController@getUpdate', 'as' => '']);
    Route::post($admin . 'themes/update/{themeId}', ['uses' => $adminController . 'ThemesController@postUpdate', 'as' => '']);
    Route::get($admin . 'themes/forms/{template?}', ['uses' => $adminController . 'ThemesController@getForms', 'as' => '']);
    Route::post($admin . 'themes/forms/{template}', ['uses' => $adminController . 'ThemesController@postForms', 'as' => '']);
    Route::get($admin . 'themes/selects/{block_id?}/{import?}', ['uses' => $adminController . 'ThemesController@getSelects', 'as' => ''])->where(array ( 'block_id' => '\w+', 'import' => '\w+', ));
    Route::post($admin . 'themes/selects/{block_id}/{import?}', ['uses' => $adminController . 'ThemesController@postSelects', 'as' => ''])->where(array ( 'block_id' => '\w+', 'import' => '\w+', ));

    Route::get($admin . 'search', ['uses' => $adminController . 'SearchController@get_index', 'as' => '']);
    Route::get($admin . 'search/middleware', ['uses' => $adminController . 'SearchController@getMiddleware', 'as' => '']);

});

// catch invalid admin routes
Route::any(config('coaster::admin.url').'/{other}', 'CoasterCms\Http\Controllers\AdminController@catchAll')->where('other', '.*');
