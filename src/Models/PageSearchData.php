<?php namespace CoasterCms\Models;

use CoasterCms\Helpers\BlockManager;

class PageSearchData extends _BaseEloquent
{

    protected $table = 'page_search_data';

    private static $_page_weights;

    public function block()
    {
        return $this->belongsTo('CoasterCms\Models\Block');
    }

    public static function update_processed_text($block_id, $search_content, $page_id, $language_id)
    {
        $updated_block = self::where('block_id', '=', $block_id)->where('page_id', '=', $page_id)->where('language_id', '=', $language_id)->first();
        if (isset($updated_block)) {
            if (!empty($search_content)) {
                $updated_block->search_text = $search_content;
                $updated_block->save();
            } else {
                $updated_block->delete();
            }
        } elseif (!empty($search_content)) {
            $block = new self;
            $block->block_id = $block_id;
            $block->page_id = $page_id;
            $block->language_id = $language_id;
            $block->search_text = $search_content;
            $block->save();
        }
    }

    public static function update_text($block_id, $block_content, $page_id, $language_id, $version = 0)
    {
        $block = Block::preload($block_id);
        if (!empty($block)) {
            $block_type = $block->get_class();
            if (method_exists($block_type, 'search_text')) {
                if (ucwords($block->type) == 'Repeater') {
                    $search_text = $block_type::search_text($block_content, $version);
                } else {
                    $search_text = $block_type::search_text($block_content);
                }
                self::update_processed_text($block_id, $search_text, $page_id, $language_id);
            }
        } else {
            self::update_processed_text(0, $block_content, $page_id, $language_id);
        }
    }

    public static function update_search_data()
    {
        self::truncate();

        $page_langs = PageLang::all();
        foreach ($page_langs as $page_lang) {
            self::update_processed_text(0, strip_tags($page_lang->name), $page_lang->page_id, $page_lang->language_id);

            $page_blocks = BlockManager::get_data_for_version(new PageBlock, $page_lang->live_version, array('language_id', 'page_id',), array($page_lang->language_id, $page_lang->page_id));
            if (!empty($page_blocks)) {
                foreach ($page_blocks as $page_block) {
                    self::update_text($page_block->block_id, $page_block->content, $page_block->page_id, $page_block->language_id, $page_lang->live_version);
                }
            }
        }
    }

    private static function _page_weights($page_id, $weight)
    {
        if (!empty(self::$_page_weights[$page_id])) {
            self::$_page_weights[$page_id] += $weight;
        } else {
            self::$_page_weights[$page_id] = $weight;
        }
    }

    public static function lookup($keywords, $live = 1, $limit = 100)
    {
        if (!empty($keywords)) {
            PageSearchLog::add_term($keywords);

            // load pages and blog connection
            $blog_pages = [];
            if (config('coaster::blog.connection') && config('coaster::blog.url')) {
                $blog_db = new \PDO(config('coaster::blog.connection'), config('coaster::blog.username'), config('coaster::blog.password'));
            }
            if ($live) {
                $cms_pages_tmp = Page::where('live', '>', 0)->get();
            } else {
                $cms_pages_tmp = Page::all();
            }
            $cms_pages = [];
            foreach ($cms_pages_tmp as $cms_page) {
                if (!$live || $cms_page->is_live()) {
                    $cms_pages[$cms_page->id] = $cms_page;
                }
            }

            // get search weights for cms & blog pages
            $keywords_array = array_merge(array($keywords), explode(' ', $keywords));
            $keywords_total = count($keywords_array);

            foreach ($keywords_array as $i => $keyword) {

                $first_word_priority = ($keywords_total - ($i + 1)) * 0.1;

                // cms search
                $page_matches = self::with('block')->where('search_text', 'LIKE', '%' . $keyword . '%')->get();
                foreach ($page_matches as $page) {
                    if (!empty($cms_pages[$page->page_id])) {
                        self::_page_weights($page->page_id, (($b = $page->block) ? (int)$b->search_weight : 2) + $first_word_priority);
                    }
                }

                // blog search
                if (!empty($blog_db)) {
                    $blog_posts = $blog_db->query("
                    SELECT ID, post_title, post_name, post_content, sum(search_weight) as search_weight
                    FROM (
                        SELECT ID, post_title, post_name, post_content, 4 AS search_weight FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish' AND post_title like '%" . $keyword . "%'
                        UNION
                        SELECT ID, post_title, post_name, post_content, 2 AS search_weight FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish' AND post_content like '%" . $keyword . "%'
                    ) results
                    GROUP BY ID, post_title, post_name, post_content
                    ORDER BY search_weight;
                    ");
                    foreach ($blog_posts as $blog_post) {
                        self::_page_weights('b' . $blog_post['ID'], ((int)$blog_post['search_weight']) + $first_word_priority);
                        $post_data = new \stdClass;
                        $post_data->id = -1;
                        $post_data->name = $blog_post['post_title'];
                        $post_data->url = config('coaster::blog.url') . $blog_post['post_name'];
                        $post_data->blog_content = $blog_post['post_content'];
                        $post_data->template = 0;
                        $blog_pages['b' . $blog_post['ID']] = $post_data;
                    }
                }

            }

            if (!empty(self::$_page_weights)) {
                // order & remove low weighted results
                asort(self::$_page_weights);
                self::$_page_weights = array_reverse(self::$_page_weights, true);
                if (count(self::$_page_weights) > $limit) {
                    $weights = array_values(self::$_page_weights);
                    foreach (self::$_page_weights as $page_id => $weight) {
                        if ($weight <= $weights[$limit]) {
                            unset(self::$_page_weights[$page_id]);
                        }
                    }
                }

                // return with data
                $pages_data = [];
                $matched_page_ids = array_keys(self::$_page_weights);
                foreach ($matched_page_ids as $page_id) {
                    if (strpos($page_id, 'b') === false) {
                        $pages_data[] = $cms_pages[$page_id];
                    } else {
                        $pages_data[] = $blog_pages[$page_id];
                    }
                }
                return $pages_data;
            }

        }
        return [];
    }

}