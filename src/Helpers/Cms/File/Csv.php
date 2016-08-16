<?php namespace CoasterCms\Helpers\Cms\File;

class Csv
{

    public static function check($src, $columns, $match = '>=')
    {
        if (file_exists($src) && ($fileHandle = fopen($src, 'r')) !== false) {
            $data = fgetcsv($fileHandle);
            rewind($fileHandle);
            if ($match == '>=') {
                if (count($data) >= $columns) {
                    return $fileHandle;
                }
            } else {
                if (count($data) == $columns) {
                    return $fileHandle;
                }
            }
        }
        return false;
    }

}