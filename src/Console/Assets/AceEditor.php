<?php namespace CoasterCms\Console\Assets;

class AceEditor extends AbstractAsset
{

    public static $name = 'ace';

    public static $version = '1.2.5';

    public static $description = 'ACE HTML/CSS/Code Editor';

    public function run()
    {
        $this->downloadZip(
            'https://github.com/ajaxorg/ace-builds/archive/v'.static::$version.'.zip',
            ['ace-builds-'.static::$version.'/src-min' => '']
        );
        $this->copyFrom();
    }

}