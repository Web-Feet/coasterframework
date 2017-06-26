<?php namespace CoasterCms\Helpers\Cms\File;

class Directory
{

    /**
     * @param string $dir
     * @param bool $keepTop
     * @param \Closure $callback
     */
    public static function remove($dir, $keepTop = false, $callback = null)
    {
        if (is_dir($dir)) {
            $dir = rtrim($dir, '/');
            foreach (scandir($dir) as $file) {
                if (!in_array($file, ['.', '..'])) {
                    $fileFullPath = $dir . DIRECTORY_SEPARATOR . $file;
                    if ($callback && !$callback($fileFullPath)) {
                        $keepTop = true;
                        continue;
                    }
                    if (is_dir($fileFullPath)) {
                        self::remove($fileFullPath, false, $callback);
                    } else {
                        unlink($fileFullPath);
                    }
                }
            }
            if (!$keepTop) {
                rmdir($dir);
            }
        }
    }

    /**
     * @param string $srcDir
     * @param string $dstDir
     * @param \Closure $callback
     * @param $overwrite
     */
    public static function copy($srcDir, $dstDir, $callback = null, $overwrite = true)
    {
        $srcDir = rtrim($srcDir, '/');
        $dstDir = rtrim($dstDir, '/');
        $dir = opendir($srcDir);
        @mkdir($dstDir, 0777, true);
        while(($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                $copyFrom = $srcDir . '/' . $file;
                $copyTo = $dstDir . '/' . $file;
                if ($callback) {
                    $callbackResult = $callback($copyFrom, $copyTo);
                    if (is_array($callbackResult)) {
                        list($copyFrom, $copyTo) = $callbackResult;
                    } elseif (!$callbackResult) {
                        continue;
                    }
                }
                if (is_dir($copyFrom)) {
                    self::copy($copyFrom, $copyTo, $callback, $overwrite);
                } elseif ($overwrite || !file_exists($copyTo)) {
                    copy($copyFrom, $copyTo);
                }
            }
        }
        closedir($dir);
    }

}