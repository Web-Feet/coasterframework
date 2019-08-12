<?php

return [

    'url' => 'admin',
    'help_link' => 'https://www.coastercms.org/documentation/user-documentation',
    'view' => realpath(__DIR__ . '/../resources/views/admin'),
    'public' => '/coaster',
    'bootstrap_version' => '3', // for pagination (supports 3 or 4)
    'title_block' => 'title',
    'default_template' => '1',
    'publishing' => '0',
    'advanced_permissions' => '0',
    'undo_time' => 3600, // limit for which deleted items can be restored for
    'always_load_routes' => '0'

];
