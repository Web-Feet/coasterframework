<?php namespace CoasterCms\Helpers\Core\Page;

class Feed
{
    /**
     * @var array
     */
    protected static $_enabledExtensions;

    /**
     * @param array $extensions
     */
    public static function enableFeedExtensions($extensions)
    {
        self::$_enabledExtensions = $extensions;
    }

    /**
     * @return array
     */
    protected static function _getEnabledFeedExtensions()
    {
        return isset(self::$_enabledExtensions) ? self::$_enabledExtensions : config('coaster::frontend.enabled_feed_extensions');
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function getFeedExtensionFromPath($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!empty(self::$_enabledExtensions) && $extension && in_array($extension, self::_getEnabledFeedExtensions())) {
            return $extension;
        }
        return false;
    }

    /**
     * @param string $path
     */
    public static function removeFeedExtensionFromPath(&$path)
    {
        if ($feedExtension = self::getFeedExtensionFromPath($path)) {
            $path = substr($path, 0, -(1 + strlen($feedExtension)));
        }
    }

    /**
     * @param string $extension
     * @return string
     */
    public static function getMimeType($extension = '')
    {
        return \GuzzleHttp\Psr7\mimetype_from_extension($extension) ?: 'text/html';
    }

}