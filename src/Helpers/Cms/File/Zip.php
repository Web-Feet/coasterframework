<?php namespace CoasterCms\Helpers\Cms\File;

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
                if (!empty($addFrom) && !empty($addTo)) {
                    if (is_dir($addFrom)) {
                        $this->addDir($addFrom, $addTo, $callback);
                    } elseif (file_exists($addFrom)) {
                        $this->addFile($addFrom, $addTo);
                    }
                }
            }
        }
        closedir($dir);
    }

    public function extractDir($localName, $dstDir)
    {
        if (!is_dir($dstDir)) {
            mkdir($dstDir, 0777, true);
        }
        for($i=0; $i<$this->numFiles; $i++) {
            $name = $this->getNameIndex($i);
            if (strpos($name, "{$localName}/") === 0 && substr($name, -1) != '/') {
                $file = $dstDir . '/' . substr($name, strlen($localName) + 1);
                $inDir = dirname($file);
                if (!is_dir($inDir)) {
                    mkdir($inDir, 0777, true);
                }
                $fpr = $this->getStream($name);
                $fpw = fopen($file, 'w');
                while ($data = fread($fpr, 1024)) {
                    fwrite($fpw, $data);
                }
                fclose($fpr);
                fclose($fpw);
            }
        }
    }

    public function extractFile($localName, $dst)
    {
        $dstDir = dirname($dst);
        if (!is_dir($dstDir)) {
            mkdir($dstDir, 0777, true);
        }
        $fpr = $this->getStream($localName);
        $fpw = fopen($dst, 'w');
        while ($data = fread($fpr, 1024)) {
            fwrite($fpw, $data);
        }
        fclose($fpr);
        fclose($fpw);
    }

}