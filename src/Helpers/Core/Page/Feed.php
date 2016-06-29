<?php namespace CoasterCms\Helpers\Core\Page;

class Feed
{
    protected static $_enabledExtensions;
    
    public static function enableFeedExtensions($extensions)
    {
        self::$_enabledExtensions = $extensions;
    }

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
        if (!empty(self::$_enabledExtensions) && !empty($path_info['extension']) && in_array($path_info['extension'], self::$_enabledExtensions)) {
            return $path_info['extension'];
        }
        return false;
    }

    public static function content_type($extension = null)
    {
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