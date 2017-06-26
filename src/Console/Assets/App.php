<?php namespace CoasterCms\Console\Assets;

class App extends AbstractAsset
{

    public static $name = 'app';

    public static $description = 'Core Admin Assets';

    public function run()
    {
        $this->copyFrom();
    }

}