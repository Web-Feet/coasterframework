<?php

$routeNamePrefix = 'coaster.admin.';
$adminUrl = config('coaster::admin.url') . '/';
$adminController = 'CoasterCms\Http\Controllers\AdminControllers';

Route::group(['middleware' => ['web', 'coaster.guest'], 'namespace' => $adminController], function () use($adminUrl, $routeNamePrefix) {

    Route::any($adminUrl . 'login', ['uses' => 'AuthController@login', 'as' => $routeNamePrefix . 'login']);
    Route::any($adminUrl . 'forgotten-password', ['uses' => 'AccountController@forgottenPassword', 'as' => $routeNamePrefix . 'login.password.forgotten']);
    Route::any($adminUrl . 'change-password/{code}', ['uses' => 'AccountController@changePassword', 'as' => $routeNamePrefix . 'login.password.change']);

});

Route::group(['middleware' => ['web', 'coaster.admin'], 'namespace' => $adminController], function () use($adminUrl, $routeNamePrefix) {

    Route::get($adminUrl, ['uses' => 'HomeController@getIndex', 'as' => rtrim($routeNamePrefix, '.')]);

    Route::get($adminUrl . 'logout', ['uses' => 'AuthController@logout', 'as' => $routeNamePrefix . 'logout']);

    Route::get($adminUrl . 'account', ['uses' => 'AccountController@getIndex', 'as' => $routeNamePrefix . 'account']);
    Route::get($adminUrl . 'account/password', ['uses' => 'AccountController@getPassword', 'as' => $routeNamePrefix . 'account.password']);
    Route::post($adminUrl . 'account/password', ['uses' => 'AccountController@postPassword', 'as' => $routeNamePrefix . 'account.password.post']);
    Route::get($adminUrl . 'account/blog', ['uses' => 'AccountController@getBlog', 'as' => $routeNamePrefix . 'account.blog']);
    Route::post($adminUrl . 'account/blog', ['uses' => 'AccountController@postBlog', 'as' => $routeNamePrefix . 'account.blog.post']);
    Route::get($adminUrl . 'account/language', ['uses' => 'AccountController@getLanguage', 'as' => $routeNamePrefix . 'account.language']);
    Route::post($adminUrl . 'account/language', ['uses' => 'AccountController@postLanguage', 'as' => $routeNamePrefix . 'account.language.post']);
    Route::post($adminUrl . 'account/page-state', ['uses' => 'AccountController@postPageState', 'as' => $routeNamePrefix . 'account.page-state']);

    Route::get($adminUrl . 'system', ['uses' => 'SystemController@getIndex', 'as' => $routeNamePrefix . 'system']);
    Route::post($adminUrl . 'system', ['uses' => 'SystemController@postIndex', 'as' => $routeNamePrefix . 'system.index.post']);
    Route::get($adminUrl . 'system/wp-login', ['uses' => 'SystemController@getWpLogin', 'as' => $routeNamePrefix . 'system.wp-login']);
    Route::get($adminUrl . 'system/search', ['uses' => 'SystemController@getSearch', 'as' => $routeNamePrefix . 'system.search']);
    Route::get($adminUrl . 'system/validate-db/{fix?}', ['uses' => 'SystemController@getValidateDb', 'as' => $routeNamePrefix . 'system.validate-db']);
    Route::get($adminUrl . 'system/upgrade/{update?}', ['uses' => 'SystemController@getUpgrade', 'as' => $routeNamePrefix . 'system.upgrade']);
    Route::post($adminUrl . 'system/keys/{key?}', ['uses' => 'SystemController@postKeys', 'as' => $routeNamePrefix . 'system.keys']);

    Route::get($adminUrl . 'home', ['uses' => 'HomeController@getIndex', 'as' => $routeNamePrefix . 'home']);
    Route::get($adminUrl . 'home/logs', ['uses' => 'HomeController@getLogs', 'as' => $routeNamePrefix . 'home.logs']);
    Route::get($adminUrl . 'home/requests', ['uses' => 'HomeController@getRequests', 'as' => $routeNamePrefix . 'home.requests']);
    Route::get($adminUrl . 'home/your-requests', ['uses' => 'HomeController@getYourRequests', 'as' => $routeNamePrefix . 'home.your-requests']);

    Route::get($adminUrl . 'pages', ['uses' => 'PagesController@getIndex', 'as' => $routeNamePrefix . 'pages']);
    Route::get($adminUrl . 'pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@getAdd', 'as' => $routeNamePrefix . 'pages.add']);
    Route::post($adminUrl . 'pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@postAdd', 'as' => $routeNamePrefix . 'pages.add.post']);
    Route::get($adminUrl . 'pages/edit/{pageId}/{version?}', ['uses' => 'PagesController@getEdit', 'as' => $routeNamePrefix . 'pages.edit'])->where(['pageId' => '\w+', 'version' => '\w+']);
    Route::post($adminUrl . 'pages/edit/{pageId}', ['uses' => 'PagesController@postEdit', 'as' => $routeNamePrefix . 'pages.edit.post']);
    Route::post($adminUrl . 'pages/sort', ['uses' => 'PagesController@postSort', 'as' => $routeNamePrefix . 'pages.sort']);
    Route::post($adminUrl . 'pages/delete/{pageId}', ['uses' => 'PagesController@postDelete', 'as' => $routeNamePrefix . 'pages.delete']);
    Route::post($adminUrl . 'pages/versions/{pageId}', ['uses' => 'PagesController@postVersions', 'as' => $routeNamePrefix . 'pages.versions']);
    Route::post($adminUrl . 'pages/version-schedule/{pageId}', ['uses' => 'PagesController@postVersionSchedule', 'as' => $routeNamePrefix . 'pages.version-schedule']);
    Route::post($adminUrl . 'pages/version-rename/{pageId}', ['uses' => 'PagesController@postVersionRename', 'as' => $routeNamePrefix . 'pages.version-rename']);
    Route::post($adminUrl . 'pages/version-publish/{pageId}', ['uses' => 'PagesController@postVersionPublish', 'as' => $routeNamePrefix . 'pages.version-publish']);
    Route::post($adminUrl . 'pages/requests/{pageId}', ['uses' => 'PagesController@postRequests', 'as' => $routeNamePrefix . 'pages.requests']);
    Route::post($adminUrl . 'pages/request-publish/{pageId}', ['uses' => 'PagesController@postRequestPublish', 'as' => $routeNamePrefix . 'pages.requests-publish']);
    Route::post($adminUrl . 'pages/request-publish-action/{pageId}', ['uses' => 'PagesController@postRequestPublishAction', 'as' => $routeNamePrefix . 'pages.requests-publish-action']);
    Route::get($adminUrl . 'pages/tinymce-page-list', ['uses' => 'PagesController@getTinymcePageList', 'as' => $routeNamePrefix . 'pages.tinymce-page-list']);

    Route::get($adminUrl . 'groups/pages/{groupId}', ['uses' => 'GroupsController@getPages', 'as' => $routeNamePrefix . 'groups.pages']);
    Route::get($adminUrl . 'groups/edit/{groupId}', ['uses' => 'GroupsController@getEdit', 'as' => $routeNamePrefix . 'groups.edit']);
    Route::post($adminUrl . 'groups/edit/{groupId}', ['uses' => 'GroupsController@postEdit', 'as' => $routeNamePrefix . 'groups.edit.post']);

    Route::get($adminUrl . 'menus', ['uses' => 'MenusController@getIndex', 'as' => $routeNamePrefix . 'menus']);
    Route::post($adminUrl . 'menus/add', ['uses' => 'MenusController@postAdd', 'as' => $routeNamePrefix . 'menus.add']);
    Route::post($adminUrl . 'menus/delete/{itemId}', ['uses' => 'MenusController@postDelete', 'as' => $routeNamePrefix . 'menus.delete']);
    Route::post($adminUrl . 'menus/sort', ['uses' => 'MenusController@postSort', 'as' => $routeNamePrefix . 'menus.sort']);
    Route::post($adminUrl . 'menus/get-levels', ['uses' => 'MenusController@postGetLevels', 'as' => $routeNamePrefix . 'menus.get-levels']);
    Route::post($adminUrl . 'menus/save-levels', ['uses' => 'MenusController@postSaveLevels', 'as' => $routeNamePrefix . 'menus.save-levels']);
    Route::post($adminUrl . 'menus/rename', ['uses' => 'MenusController@postRename', 'as' => $routeNamePrefix . 'menus.rename']);

    Route::get($adminUrl . 'blocks', ['uses' => 'BlocksController@getIndex', 'as' => $routeNamePrefix . 'blocks']);
    Route::post($adminUrl . 'blocks', ['uses' => 'BlocksController@postIndex', 'as' => $routeNamePrefix . 'blocks.index.post']);

    Route::get($adminUrl . 'filemanager', ['uses' => 'FilemanagerController@getIndex', 'as' => $routeNamePrefix . 'filemanager']);

    Route::get($adminUrl . 'redirects', ['uses' => 'RedirectsController@getIndex', 'as' => $routeNamePrefix . 'redirects']);
    Route::post($adminUrl . 'redirects', ['uses' => 'RedirectsController@postIndex', 'as' => $routeNamePrefix . 'redirects.index.post']);
    Route::get($adminUrl . 'redirects/url-decode', ['uses' => 'RedirectsController@getUrlDecode', 'as' => $routeNamePrefix . 'redirects.url-decode']);
    Route::post($adminUrl . 'redirects/edit', ['uses' => 'RedirectsController@postEdit', 'as' => $routeNamePrefix . 'redirects.edit']);
    Route::get($adminUrl . 'redirects/import', ['uses' => 'RedirectsController@getImport', 'as' => $routeNamePrefix . 'redirects.import']);

    Route::get($adminUrl . 'users', ['uses' => 'UsersController@getIndex', 'as' => $routeNamePrefix . 'users']);
    Route::get($adminUrl . 'users/edit/{userId?}/{action?}', ['uses' => 'UsersController@getEdit', 'as' => $routeNamePrefix . 'users.edit'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::post($adminUrl . 'users/edit/{userId?}/{action?}', ['uses' => 'UsersController@postEdit', 'as' => $routeNamePrefix . 'users.edit.post'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::get($adminUrl . 'users/add', ['uses' => 'UsersController@getAdd', 'as' => $routeNamePrefix . 'users.add']);
    Route::post($adminUrl . 'users/add', ['uses' => 'UsersController@postAdd', 'as' => $routeNamePrefix . 'users.add.post']);
    Route::post($adminUrl . 'users/delete/{userId?}', ['uses' => 'UsersController@postDelete', 'as' => $routeNamePrefix . 'users.delete']);

    Route::get($adminUrl . 'roles', ['uses' => 'RolesController@getIndex', 'as' => $routeNamePrefix . 'roles']);
    Route::post($adminUrl . 'roles/actions/{roleId?}', ['uses' => 'RolesController@postActions', 'as' => $routeNamePrefix . 'roles.actions']);
    Route::post($adminUrl . 'roles/add', ['uses' => 'RolesController@postAdd', 'as' => $routeNamePrefix . 'roles.add']);
    Route::post($adminUrl . 'roles/edit', ['uses' => 'RolesController@postEdit', 'as' => $routeNamePrefix . 'roles.edit']);
    Route::post($adminUrl . 'roles/delete', ['uses' => 'RolesController@postDelete', 'as' => $routeNamePrefix . 'roles.delete']);
    Route::get($adminUrl . 'roles/pages/{roleId}', ['uses' => 'RolesController@getPages', 'as' => $routeNamePrefix . 'roles.pages']);
    Route::post($adminUrl . 'roles/pages/{roleId}', ['uses' => 'RolesController@postPages', 'as' => $routeNamePrefix . 'roles.pages.post']);

    Route::get($adminUrl . 'themes', ['uses' => 'ThemesController@getIndex', 'as' => $routeNamePrefix . 'themes']);
    Route::get($adminUrl . 'themes/list', ['uses' => 'ThemesController@getList', 'as' => $routeNamePrefix . 'themes.list']);
    Route::post($adminUrl . 'themes/manage', ['uses' => 'ThemesController@postManage', 'as' => $routeNamePrefix . 'themes.manage']);
    Route::get($adminUrl . 'themes/export/{themeId}/{withPageData?}', ['uses' => 'ThemesController@getExport', 'as' => $routeNamePrefix . 'themes.export'])->where(['themeId' => '\w+', 'withPageData' => '\w+']);
    Route::get($adminUrl . 'themes/beacons', ['uses' => 'ThemesController@getBeacons', 'as' => $routeNamePrefix . 'themes.beacons']);
    Route::post($adminUrl . 'themes/beacons', ['uses' => 'ThemesController@postBeacons', 'as' => $routeNamePrefix . 'themes.beacons.post']);
    Route::get($adminUrl . 'themes/update/{themeId}', ['uses' => 'ThemesController@getUpdate', 'as' => $routeNamePrefix . 'themes.update']);
    Route::post($adminUrl . 'themes/update/{themeId}', ['uses' => 'ThemesController@postUpdate', 'as' => $routeNamePrefix . 'themes.update.post']);
    Route::get($adminUrl . 'themes/forms/{template?}', ['uses' => 'ThemesController@getForms', 'as' => $routeNamePrefix . 'themes.forms']);
    Route::post($adminUrl . 'themes/forms/{template}', ['uses' => 'ThemesController@postForms', 'as' => $routeNamePrefix . 'themes.forms.post']);
    Route::get($adminUrl . 'themes/selects/{blockId?}/{import?}', ['uses' => 'ThemesController@getSelects', 'as' => $routeNamePrefix . 'themes.selects'])->where(['blockId' => '\w+', 'import' => '\w+']);
    Route::post($adminUrl . 'themes/selects/{blockId}/{import?}', ['uses' => 'ThemesController@postSelects', 'as' => $routeNamePrefix . 'themes.selects.post'])->where(['blockId' => '\w+', 'import' => '\w+']);

    Route::get($adminUrl . 'gallery/list/{pageId?}', ['uses' => 'GalleryController@getList', 'as' => $routeNamePrefix . 'gallery.list']);
    Route::get($adminUrl . 'gallery/edit/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getEdit', 'as' => $routeNamePrefix . 'gallery.edit'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get($adminUrl . 'gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post($adminUrl . 'gallery/caption/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postCaption', 'as' => $routeNamePrefix . 'gallery.caption'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post($adminUrl . 'gallery/sort/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postSort', 'as' => $routeNamePrefix . 'gallery.sort'])->where(['pageId' => '\w+', 'blockId' => '\w+']);;
    Route::post($adminUrl . 'gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::delete($adminUrl . 'gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@deleteUpdate', 'as' => $routeNamePrefix . 'gallery.update.post'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::get($adminUrl . 'forms/list/{pageId?}', ['uses' => 'FormsController@getList', 'as' => $routeNamePrefix . 'forms.list']);
    Route::get($adminUrl . 'forms/submissions/{pageId?}/{blockId?}', ['uses' => 'FormsController@getSubmissions', 'as' => $routeNamePrefix . 'forms.submissions'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get($adminUrl . 'forms/csv/{pageId?}/{blockId?}', ['uses' => 'FormsController@getCsv', 'as' => $routeNamePrefix . 'forms.csv'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::post($adminUrl . 'backups/undo', ['uses' => 'BackupsController@postUndo', 'as' => $routeNamePrefix . 'backups.undo']);

    Route::post($adminUrl . 'repeaters', ['uses' => 'RepeatersController@postIndex', 'as' => $routeNamePrefix . 'repeaters']);

    Route::get($adminUrl . 'search', ['uses' => 'SearchController@getIndex', 'as' => $routeNamePrefix . 'search']);

});

// catch invalid admin routes
Route::any($adminUrl . '{other}', 'CoasterCms\Http\Controllers\AdminController@catchAll')->where('other', '.*');
