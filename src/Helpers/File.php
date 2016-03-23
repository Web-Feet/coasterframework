<?php namespace CoasterCms\Helpers;

class File
{

    public static function removeDirectory($dir, $keepTop = false)
    {
        foreach(scandir($dir) as $file) {
            if (in_array($file, ['.', '..'])) continue;
            $fileFullPath = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($fileFullPath)) self::removeDirectory($fileFullPath); else unlink($fileFullPath);
        }
        if (!$keepTop) {
            rmdir($dir);
        }
    }

    public static function copyDirectory($srcDir, $dstDir) {
        $srcDir = rtrim($srcDir, '/');
        $dstDir = rtrim($dstDir, '/');
        $dir = opendir($srcDir);
        @mkdir($dstDir, 0777, true);
        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                if (is_dir($srcDir . '/' . $file)) {
                    var_dump($srcDir.'/'.$file, $dstDir.'/'.$file);
                    self::copyDirectory($srcDir.'/'.$file, $dstDir.'/'.$file);
                } else {
                    var_dump($srcDir.'/'.$file, $dstDir.'/'.$file);
                    copy($srcDir.'/'.$file, $dstDir.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

}