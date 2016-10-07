<?php namespace CoasterCms\Helpers\Cms\Page\Search;

use CoasterCms\Helpers\Cms\Page\Path;

class WordPress extends Cms
{

    /**
     * @var false|\PDO
     */
    protected $_databaseConnection;

    /**
     * WordPress constructor.
     * @param bool $onlyLive
     * @param $databaseConnection
     */
    public function __construct($onlyLive, $databaseConnection)
    {
        parent::__construct($onlyLive);
        $this->_databaseConnection = $databaseConnection;
    }

    /**
     * @param string $keyword
     * @param int $keywordAdditionalWeight
     */
    public function run($keyword, $keywordAdditionalWeight = 0)
    {
        if ($this->_databaseConnection) {
            $prefix = config('coaster::blog.prefix');
            $blogPosts = $this->_databaseConnection->query("
                SELECT wp.*, weights.search_weight FROM wp_posts wp RIGHT JOIN
                    (
                        SELECT ID, sum(search_weight) as search_weight
                        FROM (
                            SELECT ID, 4 AS search_weight FROM {$prefix}posts WHERE post_type = 'post' AND post_status = 'publish' AND post_title like '%" . $keyword . "%'
                            UNION
                            SELECT ID, 2 AS search_weight FROM {$prefix}posts WHERE post_type = 'post' AND post_status = 'publish' AND post_content like '%" . $keyword . "%'
                        ) results
                        GROUP BY ID
                    ) weights
                ON weights.ID = wp.ID;
                ");
            if ($blogPosts) {
                $defaultSeparator = new Path(false);
                foreach ($blogPosts as $blogPost) {
                    $postData = new \stdClass;
                    $postData->id = 'WP' . $blogPost['ID'];
                    unset($blogPost['ID']);
                    foreach ($blogPost as $field => $value) {
                        $postData->$field = $value;
                    }
                    $postData->content = $blogPost['post_content'];
                    $postData->fullName = ucwords(str_replace('/', ' ', config('coaster::blog.url'))) . $defaultSeparator->separator . $blogPost['post_title'];
                    $postData->fullUrl = config('coaster::blog.url') . $blogPost['post_name'];
                    self::_addWeight($postData, $blogPost['search_weight'] + $keywordAdditionalWeight);
                }
            }
        }
    }

}