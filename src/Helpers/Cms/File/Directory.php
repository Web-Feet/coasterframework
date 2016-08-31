<?php namespace CoasterCms\Helpers\Cms\File;

class Directory
{

    public static function remove($dir, $keepTop = false)
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (in_array($file, ['.', '..'])) continue;
                $fileFullPath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fileFullPath)) self::remove($fileFullPath); else unlink($fileFullPath);
            }
            if (!$keepTop) {
                rmdir($dir);
            }
        }
    }

    public static function copy($srcDir, $dstDir, $callback = '')
    {
        $srcDir = rtrim($srcDir, '/');
        $dstDir = rtrim($dstDir, '/');
        $dir = opendir($srcDir);
        @mkdir($dstDir, 0777, true);
        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                $addFrom = $srcDir . '/' . $file;
                $addTo = $dstDir . '/' . $file;
                if ($callback) {
                    list($addFrom, $addTo) = $callback($addFrom, $addTo);
                    if (dirname($addTo) != $dstDir) {
                        @mkdir(dirname($addTo), 0777, true);
                    }
                }
                if (is_dir($addFrom)) {
                    self::copy($addFrom, $addTo);
                } else {
                    copy($addFrom, $addTo);
                }
            }
        }
        closedir($dir);
    }

}