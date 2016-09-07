<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class PageGroupPage extends Eloquent
{
    use DataPreLoad;

    /**
     * @var string
     */
    protected $table = 'page_group_pages';

    /**
     * @param $pageId
     * @return array
     */
    public static function getGroupIds($pageId)
    {
        static::_preloadOnce(null, 'pageGroups', ['page_id'], 'group_id', true);
        return array_unique(static::_preloadGet('pageGroups', $pageId) ?: []);
    }

}