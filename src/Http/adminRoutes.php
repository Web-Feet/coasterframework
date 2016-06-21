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
            $adminController = 'CoasterCms\Http\Controllers\Backend\\' . ucwords($controller->controller) . 'Controller';
            $adminControllerMethods = get_class_methods($adminController);
            foreach ($adminControllerMethods as $adminControllerMethod) {
                $methodParts = explode('_', snake_case($adminControllerMethod));
                if (in_array(strtolower($methodParts[0]), ['get', 'post', 'update', 'delete'])) {
                    $refMethod = new ReflectionMethod($adminController, $adminControllerMethod);
                    $methodParams = '';
                    $methodParamsArr = [];
                    foreach ($refMethod->getParameters() as $parameter) {
                        $methodParams .= '/{' . $parameter->getName() . ($parameter->isOptional()?'?':'') . '}';
                        $methodParamsArr[$parameter->getName()] = '\w+';
                    }
                    $methodAction = ($methodParts[1] != 'index') ? '/' . implode('-', array_slice($methodParts, 1)) : '';
                    call_user_func_array(
                        [
                            Route::class,
                            $methodParts[0]
                        ],
                        [
                            config('coaster::admin.url') . '/' . $controller->controller . $methodAction . $methodParams,
                            $adminController . '@' . $adminControllerMethod
                        ]
                    )->where($methodParamsArr);
                }
            }
        }
    }
});

// catch invalid admin routes
Route::any(config('coaster::admin.url').'/{all}', 'CoasterCms\Http\Controllers\AdminController@catchAll')->where('all', '.*');
