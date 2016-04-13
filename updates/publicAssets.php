<?php

$coasterPublicFolder = public_path() . config('coaster::admin.public');
$updateFrom = realpath(__DIR__.'/../public');

// update app folder
if (file_exists($coasterPublicFolder . '/app/')) {
    \CoasterCms\Helpers\File::copyDirectory($updateFrom . '/app/', $coasterPublicFolder . '/app/');
    echo "Coaster Framework: Updated public coaster app assets\n";
}