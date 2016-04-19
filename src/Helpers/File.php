<?php namespace CoasterCms\Helpers;

class File
{

    public static function removeDirectory($dir, $keepTop = false)
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (in_array($file, ['.', '..'])) continue;
                $fileFullPath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fileFullPath)) self::removeDirectory($fileFullPath); else unlink($fileFullPath);
            }
            if (!$keepTop) {
                rmdir($dir);
            }
        }
    }

    public static function copyDirectory($srcDir, $dstDir, $callback = '')
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
                    self::copyDirectory($addFrom, $addTo);
                } else {
                    copy($addFrom, $addTo);
                }
            }
        }
        closedir($dir);
    }

    public static function insertAtLine($src, array $insertions)
    {
        $fileHandle = fopen($src, 'r') or die("couldn't open $src");
        $lineNo = 1;
        $newLines = [];
        $insertLines = array_keys($insertions);
        while (($currentLine = fgets($fileHandle, 4096)) !== false) {

            if (in_array($lineNo, $insertLines)) {
                foreach ($insertions[$lineNo] as $line) {
                    $newLines[] = $line . "\r\n";
                }
                $newLines[] = $currentLine;
            } else {
                $newLines[] = $currentLine;
            }
            $lineNo++;
        }
        fclose($fileHandle);

        $fileHandle = fopen($src, 'w') or die("couldn't open $src");
        foreach ($newLines as $newLine) {
            fwrite($fileHandle, $newLine);
        }
        fclose($fileHandle);
    }

    public static function replaceString($src, $match, $replace)
    {
        $fileContent = file_get_contents($src);
        $str = str_replace($match, $replace, $fileContent);
        file_put_contents($src, $str);
    }

}