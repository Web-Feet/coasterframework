<?php

$routeNamePrefix = 'coaster.admin.';
$adminUrl = config('coaster::admin.url') . '/';
$adminController = 'CoasterCms\Http\Controllers\AdminControllers';

Route::group(['prefix' => $adminUrl, 'middleware' => ['web', 'coaster.guest'], 'namespace' => $adminController], function () use($adminUrl, $routeNamePrefix) {

    Route::any('login', ['uses' => 'AuthController@login', 'as' => $routeNamePrefix . 'login']);
    Route::any('forgotten-password', ['uses' => 'AccountController@forgottenPassword', 'as' => $routeNamePrefix . 'login.password.forgotten']);
    Route::any('change-password/{code}', ['uses' => 'AccountController@changePassword', 'as' => $routeNamePrefix . 'login.password.change']);

});

Route::group(['prefix' => $adminUrl, 'middleware' => ['web', 'coaster.admin'], 'namespace' => $adminController], function () use($adminUrl, $routeNamePrefix) {

    Route::get('/', ['uses' => 'HomeController@getIndex', 'as' => rtrim($routeNamePrefix, '.')]);

    Route::get('logout', ['uses' => 'AuthController@logout', 'as' => $routeNamePrefix . 'logout']);

    Route::get('account', ['uses' => 'AccountController@getIndex', 'as' => $routeNamePrefix . 'account']);
    Route::get('account/password', ['uses' => 'AccountController@getPassword', 'as' => $routeNamePrefix . 'account.password']);
    Route::post('account/password', ['uses' => 'AccountController@postPassword', 'as' => $routeNamePrefix . 'account.password.post']);
    Route::get('account/blog', ['uses' => 'AccountController@getBlog', 'as' => $routeNamePrefix . 'account.blog']);
    Route::post('account/blog', ['uses' => 'AccountController@postBlog', 'as' => $routeNamePrefix . 'account.blog.post']);
    Route::get('account/language', ['uses' => 'AccountController@getLanguage', 'as' => $routeNamePrefix . 'account.language']);
    Route::post('account/language', ['uses' => 'AccountController@postLanguage', 'as' => $routeNamePrefix . 'account.language.post']);
    Route::post('account/page-state', ['uses' => 'AccountController@postPageState', 'as' => $routeNamePrefix . 'account.page-state']);
    Route::get('account/name', ['uses' => 'AccountController@getName', 'as' => $routeNamePrefix . 'account.name']);
    Route::post('account/name', ['uses' => 'AccountController@postName', 'as' => $routeNamePrefix . 'account.name.post']);

    Route::get('system', ['uses' => 'SystemController@getIndex', 'as' => $routeNamePrefix . 'system']);
    Route::post('system', ['uses' => 'SystemController@postIndex', 'as' => $routeNamePrefix . 'system.index.post']);
    Route::get('system/wp-login', ['uses' => 'SystemController@getWpLogin', 'as' => $routeNamePrefix . 'system.wp-login']);
    Route::get('system/search', ['uses' => 'SystemController@getSearch', 'as' => $routeNamePrefix . 'system.search']);
    Route::get('system/validate-db/{fix?}', ['uses' => 'SystemController@getValidateDb', 'as' => $routeNamePrefix . 'system.validate-db']);
    Route::get('system/upgrade/{update?}', ['uses' => 'SystemController@getUpgrade', 'as' => $routeNamePrefix . 'system.upgrade']);
    Route::post('system/keys/{key?}', ['uses' => 'SystemController@postKeys', 'as' => $routeNamePrefix . 'system.keys']);

    Route::get('home', ['uses' => 'HomeController@getIndex', 'as' => $routeNamePrefix . 'home']);
    Route::get('home/logs', ['uses' => 'HomeController@getLogs', 'as' => $routeNamePrefix . 'home.logs']);
    Route::get('home/requests', ['uses' => 'HomeController@getRequests', 'as' => $routeNamePrefix . 'home.requests']);
    Route::get('home/your-requests', ['uses' => 'HomeController@getYourRequests', 'as' => $routeNamePrefix . 'home.your-requests']);

    Route::get('pages', ['uses' => 'PagesController@getIndex', 'as' => $routeNamePrefix . 'pages']);
    Route::get('pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@getAdd', 'as' => $routeNamePrefix . 'pages.add']);
    Route::post('pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@postAdd', 'as' => $routeNamePrefix . 'pages.add.post']);
    Route::get('pages/edit/{pageId}/{version?}', ['uses' => 'PagesController@getEdit', 'as' => $routeNamePrefix . 'pages.edit'])->where(['pageId' => '\w+', 'version' => '\w+']);
    Route::post('pages/edit/{pageId}/{version?}', ['uses' => 'PagesController@postEdit', 'as' => $routeNamePrefix . 'pages.edit.post']);
    Route::post('pages/sort', ['uses' => 'PagesController@postSort', 'as' => $routeNamePrefix . 'pages.sort']);
    Route::post('pages/delete/{pageId}', ['uses' => 'PagesController@postDelete', 'as' => $routeNamePrefix . 'pages.delete']);
    Route::post('pages/versions/{pageId}', ['uses' => 'PagesController@postVersions', 'as' => $routeNamePrefix . 'pages.versions']);
    Route::post('pages/version-schedule/{pageId}', ['uses' => 'PagesController@postVersionSchedule', 'as' => $routeNamePrefix . 'pages.version-schedule']);
    Route::post('pages/version-rename/{pageId}', ['uses' => 'PagesController@postVersionRename', 'as' => $routeNamePrefix . 'pages.version-rename']);
    Route::post('pages/version-publish/{pageId}', ['uses' => 'PagesController@postVersionPublish', 'as' => $routeNamePrefix . 'pages.version-publish']);
    Route::post('pages/requests/{pageId}', ['uses' => 'PagesController@postRequests', 'as' => $routeNamePrefix . 'pages.requests']);
    Route::post('pages/request-publish/{pageId}', ['uses' => 'PagesController@postRequestPublish', 'as' => $routeNamePrefix . 'pages.request-publish']);
    Route::post('pages/request-publish-action/{pageId}', ['uses' => 'PagesController@postRequestPublishAction', 'as' => $routeNamePrefix . 'pages.request-publish-action']);
    Route::get('pages/tinymce-page-list', ['uses' => 'PagesController@getTinymcePageList', 'as' => $routeNamePrefix . 'pages.tinymce-page-list']);

    Route::get('groups/pages/{groupId}', ['uses' => 'GroupsController@getPages', 'as' => $routeNamePrefix . 'groups.pages']);
    Route::get('groups/edit/{groupId}', ['uses' => 'GroupsController@getEdit', 'as' => $routeNamePrefix . 'groups.edit']);
    Route::post('groups/edit/{groupId}', ['uses' => 'GroupsController@postEdit', 'as' => $routeNamePrefix . 'groups.edit.post']);

    Route::get('menus', ['uses' => 'MenusController@getIndex', 'as' => $routeNamePrefix . 'menus']);
    Route::post('menus/add', ['uses' => 'MenusController@postAdd', 'as' => $routeNamePrefix . 'menus.add']);
    Route::post('menus/delete/{itemId}', ['uses' => 'MenusController@postDelete', 'as' => $routeNamePrefix . 'menus.delete']);
    Route::post('menus/sort', ['uses' => 'MenusController@postSort', 'as' => $routeNamePrefix . 'menus.sort']);
    Route::post('menus/get-levels', ['uses' => 'MenusController@postGetLevels', 'as' => $routeNamePrefix . 'menus.get-levels']);
    Route::post('menus/save-levels', ['uses' => 'MenusController@postSaveLevels', 'as' => $routeNamePrefix . 'menus.save-levels']);
    Route::post('menus/rename', ['uses' => 'MenusController@postRename', 'as' => $routeNamePrefix . 'menus.rename']);

    Route::get('blocks', ['uses' => 'BlocksController@getIndex', 'as' => $routeNamePrefix . 'blocks']);
    Route::post('blocks', ['uses' => 'BlocksController@postIndex', 'as' => $routeNamePrefix . 'blocks.index.post']);

    Route::get('filemanager', ['uses' => 'FilemanagerController@getIndex', 'as' => $routeNamePrefix . 'filemanager']);

    Route::get('redirects', ['uses' => 'RedirectsController@getIndex', 'as' => $routeNamePrefix . 'redirects']);
    Route::post('redirects', ['uses' => 'RedirectsController@postIndex', 'as' => $routeNamePrefix . 'redirects.index.post']);
    Route::get('redirects/url-decode', ['uses' => 'RedirectsController@getUrlDecode', 'as' => $routeNamePrefix . 'redirects.url-decode']);
    Route::post('redirects/edit', ['uses' => 'RedirectsController@postEdit', 'as' => $routeNamePrefix . 'redirects.edit']);
    Route::get('redirects/import', ['uses' => 'RedirectsController@getImport', 'as' => $routeNamePrefix . 'redirects.import']);

    Route::get('users', ['uses' => 'UsersController@getIndex', 'as' => $routeNamePrefix . 'users']);
    Route::get('users/edit/{userId?}/{action?}', ['uses' => 'UsersController@getEdit', 'as' => $routeNamePrefix . 'users.edit'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::post('users/edit/{userId?}/{action?}', ['uses' => 'UsersController@postEdit', 'as' => $routeNamePrefix . 'users.edit.post'])->where(['userId' => '\w+', 'action' => '\w+']);
    Route::get('users/add', ['uses' => 'UsersController@getAdd', 'as' => $routeNamePrefix . 'users.add']);
    Route::post('users/add', ['uses' => 'UsersController@postAdd', 'as' => $routeNamePrefix . 'users.add.post']);
    Route::post('users/delete/{userId?}', ['uses' => 'UsersController@postDelete', 'as' => $routeNamePrefix . 'users.delete']);

    Route::get('roles', ['uses' => 'RolesController@getIndex', 'as' => $routeNamePrefix . 'roles']);
    Route::post('roles/actions/{roleId?}', ['uses' => 'RolesController@postActions', 'as' => $routeNamePrefix . 'roles.actions']);
    Route::post('roles/add', ['uses' => 'RolesController@postAdd', 'as' => $routeNamePrefix . 'roles.add']);
    Route::post('roles/edit', ['uses' => 'RolesController@postEdit', 'as' => $routeNamePrefix . 'roles.edit']);
    Route::post('roles/delete', ['uses' => 'RolesController@postDelete', 'as' => $routeNamePrefix . 'roles.delete']);
    Route::get('roles/pages/{roleId}', ['uses' => 'RolesController@getPages', 'as' => $routeNamePrefix . 'roles.pages']);
    Route::post('roles/pages/{roleId}', ['uses' => 'RolesController@postPages', 'as' => $routeNamePrefix . 'roles.pages.post']);

    Route::get('themes', ['uses' => 'ThemesController@getIndex', 'as' => $routeNamePrefix . 'themes']);
    Route::get('themes/list', ['uses' => 'ThemesController@getList', 'as' => $routeNamePrefix . 'themes.list']);
    Route::post('themes/manage', ['uses' => 'ThemesController@postManage', 'as' => $routeNamePrefix . 'themes.manage']);
    Route::get('themes/export/{themeId}/{withPageData?}', ['uses' => 'ThemesController@getExport', 'as' => $routeNamePrefix . 'themes.export'])->where(['themeId' => '\w+', 'withPageData' => '\w+']);
    Route::get('themes/beacons', ['uses' => 'ThemesController@getBeacons', 'as' => $routeNamePrefix . 'themes.beacons']);
    Route::post('themes/beacons', ['uses' => 'ThemesController@postBeacons', 'as' => $routeNamePrefix . 'themes.beacons.post']);
    Route::get('themes/edit/{themeId}', ['uses' => 'ThemesController@getEdit', 'as' => $routeNamePrefix . 'themes.edit']);
    Route::post('themes/edit/{themeId}', ['uses' => 'ThemesController@postEdit', 'as' => $routeNamePrefix . 'themes.edit.post']);
    Route::post('themes/loadtemplatefile', ['uses' => 'ThemesController@loadTemplateFile', 'as' => $routeNamePrefix . 'themes.edit.loadfile']);
    Route::get('themes/update/{themeId}', ['uses' => 'ThemesController@getUpdate', 'as' => $routeNamePrefix . 'themes.update']);
    Route::post('themes/update/{themeId}', ['uses' => 'ThemesController@postUpdate', 'as' => $routeNamePrefix . 'themes.update.post']);
    Route::get('themes/forms/{template?}', ['uses' => 'ThemesController@getForms', 'as' => $routeNamePrefix . 'themes.forms']);
    Route::post('themes/forms/{template}', ['uses' => 'ThemesController@postForms', 'as' => $routeNamePrefix . 'themes.forms.post']);
    Route::get('themes/selects/{blockId?}/{import?}', ['uses' => 'ThemesController@getSelects', 'as' => $routeNamePrefix . 'themes.selects'])->where(['blockId' => '\w+', 'import' => '\w+']);
    Route::post('themes/selects/{blockId}/{import?}', ['uses' => 'ThemesController@postSelects', 'as' => $routeNamePrefix . 'themes.selects.post'])->where(['blockId' => '\w+', 'import' => '\w+']);

    Route::get('gallery/list/{pageId?}', ['uses' => 'GalleryController@getList', 'as' => $routeNamePrefix . 'gallery.list']);
    Route::get('gallery/edit/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getEdit', 'as' => $routeNamePrefix . 'gallery.edit'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post('gallery/caption/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postCaption', 'as' => $routeNamePrefix . 'gallery.caption'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::post('gallery/sort/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postSort', 'as' => $routeNamePrefix . 'gallery.sort'])->where(['pageId' => '\w+', 'blockId' => '\w+']);;
    Route::post('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postUpdate', 'as' => $routeNamePrefix . 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::delete('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@deleteUpdate', 'as' => $routeNamePrefix . 'gallery.update.post'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::get('forms/list/{pageId?}', ['uses' => 'FormsController@getList', 'as' => $routeNamePrefix . 'forms.list']);
    Route::get('forms/submissions/{pageId?}/{blockId?}', ['uses' => 'FormsController@getSubmissions', 'as' => $routeNamePrefix . 'forms.submissions'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
    Route::get('forms/csv/{pageId?}/{blockId?}', ['uses' => 'FormsController@getCsv', 'as' => $routeNamePrefix . 'forms.csv'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

    Route::post('backups/undo', ['uses' => 'BackupsController@postUndo', 'as' => $routeNamePrefix . 'backups.undo']);

    Route::post('repeaters', ['uses' => 'RepeatersController@postIndex', 'as' => $routeNamePrefix . 'repeaters']);

    Route::get('search', ['uses' => 'SearchController@getIndex', 'as' => $routeNamePrefix . 'search']);

    Route::get('import/wp-blog', ['uses' => 'ImportWpController@getImport', 'as' => $routeNamePrefix . 'wpimport']);
    Route::post('import/wp-blog', ['uses' => 'ImportWpController@postImport', 'as' => $routeNamePrefix . 'wpimport.post']);

    Route::post('adminsearch', ['uses' => 'AdminSearchController@search', 'as' => $routeNamePrefix . 'adminsearch']);

});

// catch invalid admin routes
Route::any($adminUrl . '{other}', 'CoasterCms\Http\Controllers\AdminController@catchAll')->where('other', '.*');
