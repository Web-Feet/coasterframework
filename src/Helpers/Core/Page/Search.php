<?php namespace CoasterCms\Helpers\Core\Page;

class Search
{

    public static $searchBlock;

    public static function searchBlockExists()
    {
        if (isset($searchBlock)) {
            return boolval($searchBlock);
        } else {
            return false;
        }
    }

}