<?php

Route::get('logout', ['uses' => 'AuthController@logout', 'as' => 'logout']);

Route::get('account', ['uses' => 'AccountController@getIndex', 'as' => 'account']);
Route::get('account/password', ['uses' => 'AccountController@getPassword', 'as' => 'account.password']);
Route::post('account/password', ['uses' => 'AccountController@postPassword', 'as' => 'account.password.post']);
Route::get('account/blog', ['uses' => 'AccountController@getBlog', 'as' => 'account.blog']);
Route::post('account/blog', ['uses' => 'AccountController@postBlog', 'as' => 'account.blog.post']);
Route::get('account/language', ['uses' => 'AccountController@getLanguage', 'as' => 'account.language']);
Route::post('account/language', ['uses' => 'AccountController@postLanguage', 'as' => 'account.language.post']);
Route::post('account/page-state', ['uses' => 'AccountController@postPageState', 'as' => 'account.page-state']);
Route::get('account/name', ['uses' => 'AccountController@getName', 'as' => 'account.name']);
Route::post('account/name', ['uses' => 'AccountController@postName', 'as' => 'account.name.post']);

Route::get('system', ['uses' => 'SystemController@getIndex', 'as' => 'system']);
Route::post('system', ['uses' => 'SystemController@postIndex', 'as' => 'system.index.post']);
Route::get('system/wp-login', ['uses' => 'SystemController@getWpLogin', 'as' => 'system.wp-login']);
Route::get('system/search', ['uses' => 'SystemController@getSearch', 'as' => 'system.search']);
Route::get('system/validate-db/{fix?}', ['uses' => 'SystemController@getValidateDb', 'as' => 'system.validate-db']);
Route::get('system/upgrade/{update?}', ['uses' => 'SystemController@getUpgrade', 'as' => 'system.upgrade']);
Route::post('system/keys/{key?}', ['uses' => 'SystemController@postKeys', 'as' => 'system.keys']);

Route::get('home', ['uses' => 'HomeController@getIndex', 'as' => 'home']);
Route::get('home/logs', ['uses' => 'HomeController@getLogs', 'as' => 'home.logs']);
Route::get('home/requests', ['uses' => 'HomeController@getRequests', 'as' => 'home.requests']);
Route::get('home/your-requests', ['uses' => 'HomeController@getYourRequests', 'as' => 'home.your-requests']);

Route::get('pages', ['uses' => 'PagesController@getIndex', 'as' => 'pages']);
Route::get('pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@getAdd', 'as' => 'pages.add']);
Route::post('pages/add/{pageId?}/{groupId?}', ['uses' => 'PagesController@postAdd', 'as' => 'pages.add.post']);
Route::get('pages/edit/{pageId}/{version?}', ['uses' => 'PagesController@getEdit', 'as' => 'pages.edit'])->where(['pageId' => '\w+', 'version' => '\w+']);
Route::post('pages/edit/{pageId}/{version?}', ['uses' => 'PagesController@postEdit', 'as' => 'pages.edit.post']);
Route::post('pages/sort', ['uses' => 'PagesController@postSort', 'as' => 'pages.sort']);
Route::post('pages/delete/{pageId}', ['uses' => 'PagesController@postDelete', 'as' => 'pages.delete']);
Route::post('pages/versions/{pageId}', ['uses' => 'PagesController@postVersions', 'as' => 'pages.versions']);
Route::post('pages/version-schedule/{pageId}', ['uses' => 'PagesController@postVersionSchedule', 'as' => 'pages.version-schedule']);
Route::post('pages/version-rename/{pageId}', ['uses' => 'PagesController@postVersionRename', 'as' => 'pages.version-rename']);
Route::post('pages/version-publish/{pageId}', ['uses' => 'PagesController@postVersionPublish', 'as' => 'pages.version-publish']);
Route::post('pages/requests/{pageId}', ['uses' => 'PagesController@postRequests', 'as' => 'pages.requests']);
Route::post('pages/request-publish/{pageId}', ['uses' => 'PagesController@postRequestPublish', 'as' => 'pages.request-publish']);
Route::post('pages/request-publish-action/{pageId}', ['uses' => 'PagesController@postRequestPublishAction', 'as' => 'pages.request-publish-action']);
Route::get('pages/tinymce-page-list', ['uses' => 'PagesController@getTinymcePageList', 'as' => 'pages.tinymce-page-list']);

Route::get('groups/pages/{groupId}', ['uses' => 'GroupsController@getPages', 'as' => 'groups.pages']);
Route::get('groups/edit/{groupId}', ['uses' => 'GroupsController@getEdit', 'as' => 'groups.edit']);
Route::post('groups/edit/{groupId}', ['uses' => 'GroupsController@postEdit', 'as' => 'groups.edit.post']);

Route::get('menus', ['uses' => 'MenusController@getIndex', 'as' => 'menus']);
Route::post('menus/add', ['uses' => 'MenusController@postAdd', 'as' => 'menus.add']);
Route::post('menus/delete/{itemId}', ['uses' => 'MenusController@postDelete', 'as' => 'menus.delete']);
Route::post('menus/sort', ['uses' => 'MenusController@postSort', 'as' => 'menus.sort']);
Route::post('menus/get-levels', ['uses' => 'MenusController@postGetLevels', 'as' => 'menus.get-levels']);
Route::post('menus/save-levels', ['uses' => 'MenusController@postSaveLevels', 'as' => 'menus.save-levels']);
Route::post('menus/rename', ['uses' => 'MenusController@postRename', 'as' => 'menus.rename']);
Route::post('menus/hide-page', ['uses' => 'MenusController@postHidePage', 'as' => 'menus.hide-page']);

Route::get('blocks', ['uses' => 'BlocksController@getIndex', 'as' => 'blocks']);
Route::post('blocks', ['uses' => 'BlocksController@postIndex', 'as' => 'blocks.index.post']);

Route::get('filemanager', ['uses' => 'FilemanagerController@getIndex', 'as' => 'filemanager']);

Route::get('redirects', ['uses' => 'RedirectsController@getIndex', 'as' => 'redirects']);
Route::post('redirects', ['uses' => 'RedirectsController@postIndex', 'as' => 'redirects.index.post']);
Route::get('redirects/url-decode', ['uses' => 'RedirectsController@getUrlDecode', 'as' => 'redirects.url-decode']);
Route::post('redirects/edit', ['uses' => 'RedirectsController@postEdit', 'as' => 'redirects.edit']);
Route::get('redirects/import', ['uses' => 'RedirectsController@getImport', 'as' => 'redirects.import']);

Route::get('users', ['uses' => 'UsersController@getIndex', 'as' => 'users']);
Route::get('users/edit/{userId?}/{action?}', ['uses' => 'UsersController@getEdit', 'as' => 'users.edit'])->where(['userId' => '\w+', 'action' => '\w+']);
Route::post('users/edit/{userId?}/{action?}', ['uses' => 'UsersController@postEdit', 'as' => 'users.edit.post'])->where(['userId' => '\w+', 'action' => '\w+']);
Route::get('users/add', ['uses' => 'UsersController@getAdd', 'as' => 'users.add']);
Route::post('users/add', ['uses' => 'UsersController@postAdd', 'as' => 'users.add.post']);
Route::post('users/delete/{userId?}', ['uses' => 'UsersController@postDelete', 'as' => 'users.delete']);

Route::get('roles', ['uses' => 'RolesController@getIndex', 'as' => 'roles']);
Route::post('roles/actions/{roleId?}', ['uses' => 'RolesController@postActions', 'as' => 'roles.actions']);
Route::post('roles/add', ['uses' => 'RolesController@postAdd', 'as' => 'roles.add']);
Route::post('roles/edit', ['uses' => 'RolesController@postEdit', 'as' => 'roles.edit']);
Route::post('roles/delete', ['uses' => 'RolesController@postDelete', 'as' => 'roles.delete']);
Route::get('roles/pages/{roleId}', ['uses' => 'RolesController@getPages', 'as' => 'roles.pages']);
Route::post('roles/pages/{roleId}', ['uses' => 'RolesController@postPages', 'as' => 'roles.pages.post']);

Route::get('themes', ['uses' => 'ThemesController@getIndex', 'as' => 'themes']);
Route::get('themes/list', ['uses' => 'ThemesController@getList', 'as' => 'themes.list']);
Route::post('themes/manage', ['uses' => 'ThemesController@postManage', 'as' => 'themes.manage']);
Route::get('themes/export/{themeId}/{withPageData?}', ['uses' => 'ThemesController@getExport', 'as' => 'themes.export'])->where(['themeId' => '\w+', 'withPageData' => '\w+']);
Route::get('themes/beacons', ['uses' => 'ThemesController@getBeacons', 'as' => 'themes.beacons']);
Route::post('themes/beacons', ['uses' => 'ThemesController@postBeacons', 'as' => 'themes.beacons.post']);
Route::get('themes/edit/{themeId}', ['uses' => 'ThemesController@getEdit', 'as' => 'themes.edit']);
Route::post('themes/edit/{themeId}', ['uses' => 'ThemesController@postEdit', 'as' => 'themes.edit.post']);
Route::post('themes/loadtemplatefile', ['uses' => 'ThemesController@loadTemplateFile', 'as' => 'themes.edit.loadfile']);
Route::get('themes/update/{themeId}', ['uses' => 'ThemesController@getUpdate', 'as' => 'themes.update']);
Route::post('themes/update/{themeId}', ['uses' => 'ThemesController@postUpdate', 'as' => 'themes.update.post']);
Route::get('themes/forms/{template?}', ['uses' => 'ThemesController@getForms', 'as' => 'themes.forms']);
Route::post('themes/forms/{template}', ['uses' => 'ThemesController@postForms', 'as' => 'themes.forms.post']);
Route::get('themes/selects/{blockId?}/{import?}', ['uses' => 'ThemesController@getSelects', 'as' => 'themes.selects'])->where(['blockId' => '\w+', 'import' => '\w+']);
Route::post('themes/selects/{blockId}/{import?}', ['uses' => 'ThemesController@postSelects', 'as' => 'themes.selects.post'])->where(['blockId' => '\w+', 'import' => '\w+']);

Route::get('gallery/list/{pageId?}', ['uses' => 'GalleryController@getList', 'as' => 'gallery.list']);
Route::get('gallery/edit/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getEdit', 'as' => 'gallery.edit'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
Route::get('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@getUpdate', 'as' => 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
Route::post('gallery/caption/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postCaption', 'as' => 'gallery.caption'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
Route::post('gallery/sort/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postSort', 'as' => 'gallery.sort'])->where(['pageId' => '\w+', 'blockId' => '\w+']);;
Route::post('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@postUpdate', 'as' => 'gallery.update'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
Route::delete('gallery/update/{pageId?}/{blockId?}', ['uses' => 'GalleryController@deleteUpdate', 'as' => 'gallery.update.post'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

Route::get('forms/list/{pageId?}', ['uses' => 'FormsController@getList', 'as' => 'forms.list']);
Route::get('forms/submissions/{pageId?}/{blockId?}', ['uses' => 'FormsController@getSubmissions', 'as' => 'forms.submissions'])->where(['pageId' => '\w+', 'blockId' => '\w+']);
Route::get('forms/csv/{pageId?}/{blockId?}', ['uses' => 'FormsController@getCsv', 'as' => 'forms.csv'])->where(['pageId' => '\w+', 'blockId' => '\w+']);

Route::post('backups/undo', ['uses' => 'BackupsController@postUndo', 'as' => 'backups.undo']);

Route::post('repeaters', ['uses' => 'RepeatersController@postIndex', 'as' => 'repeaters']);

Route::get('search', ['uses' => 'SearchController@getIndex', 'as' => 'search']);

Route::get('import/wp-blog', ['uses' => 'ImportWpController@getImport', 'as' => 'wpimport']);
Route::post('import/wp-blog', ['uses' => 'ImportWpController@postImport', 'as' => 'wpimport.post']);

Route::post('adminsearch', ['uses' => 'AdminSearchController@search', 'as' => 'adminsearch']);
