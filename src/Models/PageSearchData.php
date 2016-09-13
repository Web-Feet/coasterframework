<?php namespace CoasterCms\Models;

use CoasterCms\Events\Cms\Page\Search;
use CoasterCms\Helpers\Cms\Page\Search\Cms;
use CoasterCms\Helpers\Cms\Page\Search\WordPress;
use CoasterCms\Helpers\Cms\Theme\BlockManager;
use Eloquent;

class PageSearchData extends Eloquent
{

    protected $table = 'page_search_data';

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
        if ($block->exists) {
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

    public static function lookup($keywords, $onlyLive = 1, $limit = 100)
    {
        $foundPages = [];

        if (!empty($keywords)) {
            PageSearchLog::add_term($keywords);

            // create array containing, whole term then each individual word (or just whole term if one word)
            $keywordsArray = array_merge([$keywords], explode(' ', $keywords));
            $keywordsArray = count($keywordsArray) == 2 ? [$keywordsArray[0]] : $keywordsArray;
            $numberOfKeywords = count($keywordsArray);

            // load search objects
            $searchObjects = [
                new Cms($onlyLive),
                new WordPress($onlyLive, config('coaster::blog.url') ? Setting::blogConnection() : false)
            ];
            event(new Search($searchObjects, $onlyLive));

            // run search functions
            foreach ($keywordsArray as $i => $keyword) {
                $keywordAdditionalWeight = (($numberOfKeywords - $i) * 0.1) + (($i == 0 && $numberOfKeywords > 1) ? 5 : 0);
                foreach ($searchObjects as $searchObject) {
                    $searchObject->run($keyword, $keywordAdditionalWeight);
                }
            }

            // get search results
            $weights = [];
            $pages = [];
            foreach ($searchObjects as $i => $searchObject) {
                $weights = $weights + $searchObject->getWeights();
                $pages = $pages + $searchObject->getPages();
            }

            // order, limit and create page data array
            if ($weights) {
                arsort($weights);
                if (count($weights) > $limit) {
                    $weights = array_slice($weights, 0, 100, true);
                }
                $pageIds = array_keys($weights);
                foreach ($pageIds as $pageId) {
                    if (array_key_exists($pageId, $pages)) {
                        $foundPages[] = $pages[$pageId];
                    }
                }
            }

        }

        return $foundPages;
    }

}