<?php namespace CoasterCms\Helpers;

class Zip extends \ZipArchive
{

    public function addDir($dirname, $localname = null)
    {
        $dirname = rtrim($dirname, '/');
        $localname = rtrim($localname, '/');
        $dir = opendir($dirname);
        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                if (is_dir($dirname . '/' . $file)) {
                    $this->addDir($dirname.'/'.$file, $localname.'/'.$file);
                } else {
                    $this->addFile($dirname.'/'.$file, $localname.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

}