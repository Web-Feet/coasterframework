<?php namespace CoasterCms\Helpers\Core;

class Feed
{

    public static $extension = false;

    public static function check_exists($theme, $file)
    {
        $path_info = pathinfo($file);
        if (!empty($path_info['extension']) && is_dir(base_path('/resources/views/themes/' . $theme . '/feed/' . $path_info['extension']))) {
            return $path_info['extension'];
        }
        return false;
    }

    public static function content_type($extension = null)
    {
        if (!$extension) {
            $extension = self::$extension;
        }
        switch ($extension) {
            case 'xml':
            case 'rss':
                return 'text/xml';
                break;
            case 'json':
                return 'application/json';
            default:
                return 'text/html';
        }
    }

}