<?php

return [

    'view' => realpath(__DIR__ . '/../resources/views/frontend'),
    'croppa_handle' => 'coaster.*|uploads.*|themes.*',
    'bootstrap_version' => '3', // for pagination (supports 3 or 4)
    'strong_tags' => '0',
    'form_error_class' => 'has-error',
    'external_form_input' => 'coaster',
    'language_fallback' => '0',
    'theme' => '1',
    'language' => '1',
    'canonicals' => '1',
    'enabled_feed_extensions' => 'rss,xml,json',
    'cache' => '0' // fpc cache minutes

];
