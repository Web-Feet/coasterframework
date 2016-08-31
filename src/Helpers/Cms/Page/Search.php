<?php namespace CoasterCms\Helpers\Cms\Page;

class Search
{

    /**
     * @var bool
     */
    private static $_searchBlockExists;

    /**
     * @var bool
     */
    private static $_searchBlockRequired;

    /**
     *
     */
    public static function searchBlockFound()
    {
        self::$_searchBlockExists = true;
    }

    /**
     *
     */
    public static function setSearchBlockRequired()
    {
        self::$_searchBlockRequired = true;
    }

    /**
     * @return bool
     */
    public static function searchBlockExists()
    {
        return isset(self::$_searchBlockExists) ? self::$_searchBlockExists : false;
    }

    /**
     * @return bool
     */
    public static function searchBlockRequired()
    {
        return isset(self::$_searchBlockRequired) ? self::$_searchBlockRequired : false;
    }

}