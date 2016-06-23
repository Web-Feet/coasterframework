<?php namespace CoasterCms\Helpers\Core\Page;

use CoasterCms\Libraries\Builder\PageBuilder;

class Feed
{

    public static $extension = false;

    public static function removeFeedExtension($path)
    {
        if ($feedExtension = self::getFeedExtension($path)) {
            return substr($path, 0, -(1 + strlen($feedExtension)));
        } else {
            return $path;
        }
    }

    public static function getFeedExtension($file)
    {
        $path_info = pathinfo($file);
        if (!empty($path_info['extension']) && is_dir(base_path('/resources/views/themes/' . PageBuilder::$theme . '/feed/' . $path_info['extension']))) {
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