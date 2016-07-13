<?php

$routeNamePrefix = 'coaster.admin.';
$admin = config('coaster::admin.url') . '/';
$adminController = 'CoasterCms\Http\Controllers\AdminControllers\\';

Route::group(['middleware' => ['web', 'coaster.guest']], function () use($admin, $adminController, $routeNamePrefix) {

    Route::any($admin . 'login', ['uses' => $adminController . 'AuthController@login', 'as' => $routeNamePrefix . 'login']);
    Route::any($admin . 'forgotten-password', ['uses' => $adminController . 'AccountController@forgottenPassword', 'as' => $routeNamePrefix . 'login.password.forgotten']);
    Route::any($admin . 'change-password/{code}', ['uses' => $adminController . 'AccountController@changePassword', 'as' => $routeNamePrefix . 'login.password.change']);

});

Route::group(['middleware' => ['web', 'coaster.admin']], function () use($admin, $adminController, $routeNamePrefix) {

    Route::get($admin, ['uses' => $adminController . 'HomeController@getIndex', 'as' => rtrim($routeNamePrefix, '.')]);

    Route::get($admin . 'logout', ['uses' => $adminController . 'AuthController@logout', 'as' => $routeNamePrefix . 'logout']);

    Route::get($admin . 'account', ['uses' => $adminController . 'AccountController@getIndex', 'as' => $routeNamePrefix . '']);
    Route::get($admin . 'account/password', ['uses' => $adminController . 'AccountController@getPassword', 'as' => $routeNamePrefix . 'account.password']);
    Route::post($admin . 'account/password', ['uses' => $adminController . 'AccountController@postPassword']);
    Route::get($admin . 'account/blog', ['uses' => $adminController . 'AccountController@getBlog', 'as' => $routeNamePrefix . 'account.blog']);
    Route::post($admin . 'account/blog', ['uses' => $adminController . 'AccountController@postBlog']);
    Route::get($admin . 'account/language', ['uses' => $adminController . 'AccountController@getLanguage', 'as' => $routeNamePrefix . 'account.language']);
    Route::post($admin . 'account/language', ['uses' => $adminController . 'AccountController@postLanguage']);
    Route::post($admin . 'account/page-state', ['uses' => $adminController . 'AccountController@postPageState', 'as' => $routeNamePrefix . 'account.page-state']);

    Route::get($admin . 'system', ['uses' => $adminController . 'SystemController@getIndex', 'as' => $routeNamePrefix . 'system']);
    Route::post($admin . 'system', ['uses' => $adminController . 'SystemController@postIndex']);
    Route::get($admin . 'system/wp-login', ['uses' => $adminController . 'SystemController@getWpLogin', 'as' => $routeNamePrefix . 'system.wp-login']);
    Route::get($admin . 'system/search', ['uses' => $adminController . 'SystemController@getSearch', 'as' => $routeNamePrefix . 'system.search']);
    Route::get($admin . 'system/validate-db/{fix?}', ['uses' => $adminController . 'SystemController@getValidateDb', 'as' => $routeNamePrefix . 'system.validate-db']);
    Route::get($admin . 'system/upgrade/{update?}', ['uses' => $adminController . 'SystemController@getUpgrade', 'as' => $routeNamePrefix . 'system.upgrade']);
    Route::post($admin . 'system/keys/{key?}', ['uses' => $adminController . 'SystemController@postKeys', 'as' => $routeNamePrefix . 'system.keys']);

    Route::get($admin . 'home', ['uses' => $adminController . 'HomeController@getIndex', 'as' => $routeNamePrefix . 'home']);
    Route::get($admin . 'home/logs', ['uses' => $adminController . 'HomeController@getLogs', 'as' => $routeNamePrefix . 'home.logs']);
    Route::get($admin . 'home/requests', ['uses' => $adminController . 'HomeController@getRequests', 'as' => $routeNamePrefix . 'home.requests']);
    Route::get($admin . 'home/your-requests', ['uses' => $adminController . 'HomeController@getYourRequests', 'as' => $routeNamePrefix . 'home.your-requests']);

    Route::get($admin . 'pages', ['uses' => $adminController . 'PagesController@getIndex', 'as' => $routeNamePrefix . 'pages']);
    Route::get($admin . 'pages/add/{pageId?}', ['uses' => $adminController . 'PagesController@getAdd', 'as' => $routeNamePrefix . 'pages.add']);
    Route::post($admin . 'pages/add/{pageId?}', ['uses' => $adminController . 'PagesController@postAdd']);
    Route::get($admin . 'pages/edit/{pageId}/{version?}', ['uses' => $adminController . 'PagesController@getEdit', 'as' => $routeNamePrefix . 'pages.edit'])->where(['pageId' => '\w+', 'version' => '\w+']);
    Route::post($admin . 'pages/edit/{pageId}', ['uses' => $adminController . 'PagesController@postEdit']);
    Route::post($admin . 'pages/sort', ['uses' => $adminController . 'PagesController@postSort', 'as' => $routeNamePrefix . 'pages.sort']);
    Route::post($admin . 'pages/delete/{pageId}', ['uses' => $adminController . 'PagesController@postDelete', 'as' => $routeNamePrefix . 'pages.delete']);
    Route::post($admin . 'pages/versions/{pageId}', ['uses' => $adminController . 'PagesController@postVersions', 'as' => $routeNamePrefix . 'pages.versions']);
    Route::post($admin . 'pages/version-schedule/{pageId}', ['uses' => $adminController . 'PagesController@postVersionSchedule', 'as' => $routeNamePrefix . 'pages.version-schedule']);
    Route::post($admin . 'pages/version-rename/{pageId}', ['uses' => $adminController . 'PagesController@postVersionRename', 'as' => $routeNamePrefix . 'pages.version-rename']);
    Route::post($admin . 'pages/version-publish/{pageId}', ['uses' => $adminController . 'PagesController@postVersionPublish', 'as' => $routeNamePrefix . 'pages.version-publish']);
    Route::post($admin . 'pages/requests/{pageId}', ['uses' => $adminController . 'PagesController@postRequests', 'as' => $routeNamePrefix . 'pages.requests']);
    Route::post($admin . 'pages/request-publish/{pageId}', ['uses' => $adminController . 'PagesController@postRequestPublish', 'as' => $routeNamePrefix . 'pages.requests-publish']);
    Route::post($admin . 'pages/request-publish-action/{pageId}', ['uses' => $adminController . 'PagesController@postRequestPublishAction', 'as' => $routeNamePrefix . 'pages.requests-publish-action']);
    Route::get($admin . 'pages/tinymce-page-list', ['uses' => $adminController . 'PagesController@getTinymcePageList', 'as' => $routeNamePrefix . 'pages.tinymce-page-list']);

    Route::get($admin . 'groups/pages/{groupId}', ['uses' => $adminController . 'GroupsController@getPages', 'as' => $routeNamePrefix . 'groups.pages']);

    Route::get($admin . 'menus', ['uses' => $adminController . 'MenusController@getIndex', 'as' => $routeNamePrefix . 'menus']);
    Route::post($admin . 'menus/add', ['uses' => $adminController . 'MenusController@postAdd', 'as' => $routeNamePrefix . 'menus.add']);
    Route::post($admin . 'menus/delete/{itemId}', ['uses' => $adminController . 'MenusController@postDelete', 'as' => $routeNamePrefix . 'menus.delete']);
    Route::post($admin . 'menus/sort', ['uses' => $adminController . 'MenusController@postSort', 'as' => $routeNamePrefix . 'menus.sort']);
    Route::post($admin . 'menus/get-levels', ['uses' => $adminController . 'MenusController@postGetLevels', 'as' => $routeNamePrefix . 'menus.get-levels']);
    Route::post($admin . 'menus/save-levels', ['uses' => $adminController . 'MenusController@postSaveLevels', 'as' => $routeNamePrefix . 'menus.save-levels']);
    Route::post($admin . 'menus/rename', ['uses' => $adminController . 'MenusController@postRename', 'as' => $routeNamePrefix . 'menus.rename']);

    Route::get($admin . 'blocks', ['uses' => $adminController . 'BlocksController@getIndex', 'as' => $routeNamePrefix . 'blocks']);
    Route::post($admin . 'blocks', ['uses' => $adminController . 'BlocksController@postIndex']);

    Route::get($admin . 'filemanager', ['uses' => $adminController . 'FilemanagerController@getIndex', 'as' => $routeNamePrefix . 'filemanager']);

    Route::get($admin . 'redirects', ['uses' => $adminController . 'RedirectsController@getIndex', 'as' => $routeNamePrefix . 'redirects']);
    Route::post($admin . 'redirects', ['uses' => $adminController . 'RedirectsController@postIndex']);
    Route::get($admin . 'redirects/url-decode', ['uses' => $adminController . 'RedirectsController@getUrlDecode', 'as' => $routeNamePrefix . 'redirects.url-decode']);
    Route::post($admin . 'redirects/edit', ['uses' => $adminController . 'RedirectsController@postEdit', 'as' => $routeNamePrefix . 'redirects.edit']);
    Route::get($admin . 'redirects/import', ['uses' => $adminController . 'RedirectsController@getImport', 'as' => $routeNamePrefix . 'redirects.import']);

    Route::get($admin . 'users', ['uses' => $adminController . 'UsersController@getIndex', 'as' => $routeNamePrefix . 'users']);
    Route::get($admin . 'users/edit/{userId?}/{action?}', ['uses' => $adminController . 'UsersController@getEdit', 'as' => $routeNamePrefix . 'users.edit'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::post($admin . 'users/edit/{userId?}/{action?}', ['uses' => $adminController . 'UsersController@postEdit'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::get($admin . 'users/add', ['uses' => $adminController . 'UsersController@getAdd', 'as' => $routeNamePrefix . 'users.add']);
    Route::post($admin . 'users/add', ['uses' => $adminController . 'UsersController@postAdd']);
    Route::post($admin . 'users/delete/{userId?}', ['uses' => $adminController . 'UsersController@postDelete', 'as' => $routeNamePrefix . 'users.delete']);

    Route::get($admin . 'roles', ['uses' => $adminController . 'RolesController@getIndex', 'as' => $routeNamePrefix . 'roles']);
    Route::post($admin . 'roles/actions/{roleId?}', ['uses' => $adminController . 'RolesController@postActions', 'as' => $routeNamePrefix . 'roles.actions']);
    Route::post($admin . 'roles/add', ['uses' => $adminController . 'RolesController@postAdd', 'as' => $routeNamePrefix . 'roles.add']);
    Route::post($admin . 'roles/edit', ['uses' => $adminController . 'RolesController@postEdit', 'as' => $routeNamePrefix . 'roles.edit']);
    Route::post($admin . 'roles/delete', ['uses' => $adminController . 'RolesController@postDelete', 'as' => $routeNamePrefix . 'roles.delete']);
    Route::get($admin . 'roles/pages/{roleId}', ['uses' => $adminController . 'RolesController@getPages', 'as' => $routeNamePrefix . 'roles.pages']);
    Route::post($admin . 'roles/pages/{roleId}', ['uses' => $adminController . 'RolesController@postPages']);

    Route::get($admin . 'themes', ['uses' => $adminController . 'ThemesController@getIndex', 'as' => $routeNamePrefix . 'themes']);
    Route::get($admin . 'themes/list', ['uses' => $adminController . 'ThemesController@getList', 'as' => $routeNamePrefix . 'themes.list']);
    Route::post($admin . 'themes/manage', ['uses' => $adminController . 'ThemesController@postManage', 'as' => $routeNamePrefix . 'themes.manage']);
    Route::get($admin . 'themes/export/{themeId}/{withPageData?}', ['uses' => $adminController . 'ThemesController@getExport', 'as' => $routeNamePrefix . 'themes.export'])->where(['themeId' => '\w+', 'withPageData' => '\w+']);
    Route::get($admin . 'themes/beacons', ['uses' => $adminController . 'ThemesController@getBeacons', 'as' => $routeNamePrefix . 'themes.beacons']);
    Route::post($admin . 'themes/beacons', ['uses' => $adminController . 'ThemesController@postBeacons']);
    Route::get($admin . 'themes/update/{themeId}', ['uses' => $adminController . 'ThemesController@getUpdate', 'as' => $routeNamePrefix . 'themes.update']);
    Route::post($admin . 'themes/update/{themeId}', ['uses' => $adminController . 'ThemesController@postUpdate']);
    Route::get($admin . 'themes/forms/{template?}', ['uses' => $adminController . 'ThemesController@getForms', 'as' => $routeNamePrefix . 'themes.forms']);
    Route::post($admin . 'themes/forms/{template}', ['uses' => $adminController . 'ThemesController@postForms']);
    Route::get($admin . 'themes/selects/{blockId?}/{import?}', ['uses' => $adminController . 'ThemesController@getSelects', 'as' => $routeNamePrefix . 'themes.selects'])->where(['blockId' => '\w+', 'import' => '\w+']);
    Route::post($admin . 'themes/selects/{blockId}/{import?}', ['uses' => $adminController . 'ThemesController@postSelects'])->where(['blockId' => '\w+', 'import' => '\w+']);

    Route::get($admin . 'gallery/list/{pageId?}', ['uses' => $adminController . 'GalleryController@getList', 'as' => $routeNamePrefix . 'gallery.list']);
    Route::get($admin . 'gallery/edit/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@getEdit', 'as' => $routeNamePrefix . 'gallery.edit'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get($admin . 'gallery/update/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@getUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post($admin . 'gallery/caption/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@postCaption', 'as' => $routeNamePrefix . 'gallery.caption'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post($admin . 'gallery/sort/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@postSort', 'as' => $routeNamePrefix . 'gallery.sort'])->where(['pageId' => '\w+', 'blockId' => '\w+']);;
    Route::post($admin . 'gallery/update/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@postUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::delete($admin . 'gallery/update/{pageId?}/{blockId?}', ['uses' => $adminController . 'GalleryController@deleteUpdate'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::get($admin . 'forms/list/{pageId?}', ['uses' => $adminController . 'FormsController@getList', 'as' => $routeNamePrefix . 'forms.list']);
    Route::get($admin . 'forms/submissions/{pageId?}/{blockId?}', ['uses' => $adminController . 'FormsController@getSubmissions', 'as' => $routeNamePrefix . 'forms.submissions'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get($admin . 'forms/csv/{pageId?}/{blockId?}', ['uses' => $adminController . 'FormsController@getCsv', 'as' => $routeNamePrefix . 'forms.csv'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::post($admin . 'backups/undo', ['uses' => $adminController . 'BackupsController@postUndo', 'as' => $routeNamePrefix . 'backups.undo']);

    Route::post($admin . 'repeaters', ['uses' => $adminController . 'RepeatersController@postIndex', 'as' => $routeNamePrefix . 'repeaters']);

    Route::get($admin . 'search', ['uses' => $adminController . 'SearchController@getIndex', 'as' => $routeNamePrefix . 'search']);

});

// catch invalid admin routes
Route::any(config('coaster::admin.url').'/{other}', 'CoasterCms\Http\Controllers\AdminController@catchAll')->where('other', '.*');
