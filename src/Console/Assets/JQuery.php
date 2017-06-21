<?php namespace CoasterCms\Console\Assets;

class JQuery extends AbstractAsset
{

    public static $name = 'jquery';

    public static $version = '1.12.0';

    public static $description = 'jQuery';

    public function run()
    {
        $this->downloadFile('https://code.jquery.com/jquery-1.12.0.min.js');
        $this->downloadFile('https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.js');
        $this->downloadZip(
            'https://github.com/ilikenwf/nestedSortable/archive/master.zip',
            ['nestedSortable-master/jquery.mjs.nestedSortable.js' => 'jquery.mjs.nestedSortable.js']
        );
    }

}