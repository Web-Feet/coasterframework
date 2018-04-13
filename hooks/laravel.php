<?php
/**
 * Load laravel framework with http kernel, decrypted cookies and sessions
 */

require __DIR__ . '/../../../../bootstrap/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::capture());
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

/** @var \Symfony\Component\HttpFoundation\ParameterBag $requestCookies */
$requestCookies = $app->make('request')->cookies;
foreach ($requestCookies->all() as $key => $cookie) {
    try {
        $cookie = is_string($cookie) ? $app->make('encrypter')->decrypt($cookie) : null;
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        $cookie = null;
    }
    $requestCookies->set($key, $cookie);
}

/** @var Illuminate\Session\SessionInterface $session */
$session = $app->make('session')->driver();
$session->setId($requestCookies->get($app->make('config')->get('session.cookie')));
$session->start();
