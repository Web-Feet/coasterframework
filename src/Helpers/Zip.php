<?php namespace CoasterCms\Helpers;

class Zip extends \ZipArchive
{

    public function addDir($dirName, $localName = null, $callback = '')
    {
        $dirName = rtrim($dirName, '/');
        $localName = rtrim($localName, '/');
        $dir = opendir($dirName);
        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                $addFrom = $dirName . '/' . $file;
                $addTo = $localName . '/' . $file;
                if ($callback) {
                    list($addFrom, $addTo) = $callback($addFrom, $addTo);
                }
                if (!empty($addFrom) && !empty($addToDir)) {
                    if (is_dir($addFrom)) {
                        $this->addDir($addFrom, $addTo, $callback);
                    } else {
                        $this->addFile($addFrom, $addTo);
                    }
                }
            }
        }
        closedir($dir);
    }

}