<?php namespace CoasterCms\Console\Assets;

class Bootstrap extends AbstractAsset
{

    public static $name = 'bootstrap';

    public static $version = '3.3.6';

    public static $description = 'Twitter Bootstrap';

    public function run()
    {
        $this->downloadZip(
            'https://github.com/twbs/bootstrap/releases/download/v3.3.6/bootstrap-3.3.6-dist.zip',
            ['bootstrap-3.3.6-dist' => '']
        );
    }

}