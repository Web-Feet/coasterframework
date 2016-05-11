<?php

echo "Coaster Framework: Running database migrations\n";
Artisan::call('migrate', ['--path' => '/vendor/web-feet/coasterframework/database/migrations']);
echo Artisan::output();