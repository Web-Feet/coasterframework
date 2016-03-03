<?php
require __DIR__ . '/../../../../bootstrap/autoload.php';
$laravel_app = require __DIR__ . '/../../../../bootstrap/app.php';

$kernel = $laravel_app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
