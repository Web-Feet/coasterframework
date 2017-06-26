<?php namespace CoasterCms\Console\Assets;

class Themes extends AbstractAsset
{

    public static $name = 'themes';

    public static $version = '-';

    public static $description = 'Default Coaster Themes';

    public function run()
    {
        $this->downloadZip(
            'https://github.com/Web-Feet/coasterthemes/archive/master.zip',
            ['coasterthemes-master' => resource_path('views/themes')],
            'GET',
            [],
            false,
            $this->_options['default-themes']
        );
    }

}