<?php

// load laravel framework (for filemanager)
require __DIR__ . '/../../../../bootstrap/autoload.php';
$laravel_app = require __DIR__ . '/../../../../bootstrap/app.php';

$kernel = $laravel_app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// start sessions for auth to work

$session = $laravel_app['session']->driver();
$encrypter = $laravel_app['encrypter'];
$request_cookies = $laravel_app['request']->cookies;

foreach ($request_cookies as $key => $cookie) {
    try {
        $decrypted_cookie = is_array($cookie) ? array_map(array($encrypter, 'decrypt'), $cookie) : $encrypter->decrypt($cookie);
        $request_cookies->set($key, $decrypted_cookie);
        if ($key == $laravel_app['config']['session.cookie']) {
            $session->setId($decrypted_cookie);
        }
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        $request_cookies->set($key, null);
    }
}

$session->start();