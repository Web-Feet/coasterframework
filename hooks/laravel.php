<?php
/**
 * Load laravel framework with http kernel, decrypted cookies and sessions
 */

require __DIR__ . '/../../../../bootstrap/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::capture());
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

/** @var \Symfony\Component\HttpFoundation\ParameterBag $requestCookies */
$request = $app->make('request');

// make decrypt public and run
class IlluminateEncryptCookies extends \Illuminate\Cookie\Middleware\EncryptCookies
{
    public function decrypt(\Symfony\Component\HttpFoundation\Request $request) {
        return parent::decrypt($request);
    }
}
$app->make('IlluminateEncryptCookies')->decrypt($request);

/** @var Illuminate\Contracts\Session\Session $session */
$session = $app->make('session')->driver();
$session->setId($request->cookies->get($app->make('config')->get('session.cookie')));
$session->start();
