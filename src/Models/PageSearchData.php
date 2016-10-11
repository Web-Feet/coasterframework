<?php namespace CoasterCms\Models;

use CoasterCms\Events\Cms\Page\Search;
use CoasterCms\Helpers\Cms\Page\Search\Cms;
use CoasterCms\Helpers\Cms\Page\Search\WordPress;
use Eloquent;

class PageSearchData extends Eloquent
{

    protected $table = 'page_search_data';

    public function block()
    {
        return $this->belongsTo('CoasterCms\Models\Block');
    }

    public static function updateText($content, $blockId, $pageId, $languageId = null)
    {
        $languageId = $languageId ?: Language::current();
        $searchData = static::where('block_id', '=', $blockId)->where('page_id', '=', $pageId)->where('language_id', '=', $languageId)->first() ?: new static;
        if ($content) {
            $searchData->block_id = $blockId;
            $searchData->page_id = $pageId;
            $searchData->language_id = Language::current();
            $searchData->search_text = $content;
            $searchData->save();
        } elseif ($searchData->exists) {
            $searchData->delete();
        }
    }

    public static function updateAllSearchData()
    {
        self::truncate();
        $pageLanguages = PageLang::all();
        foreach ($pageLanguages as $pageLang) {
            static::updateText(strip_tags($pageLang->name), 0, $pageLang->page_id, $pageLang->language_id);
            $pageBlocks = Block::getDataForVersion(new PageBlock, $pageLang->live_version, ['language_id' => $pageLang->language_id, 'page_id' => $pageLang->page_id]);
            foreach ($pageBlocks as $pageBlock) {
                $block = Block::preloadClone($pageBlock->block_id)->setPageId($pageBlock->page_id);
                $searchText = $block->search_weight > 0 ? $block->getTypeObject()->generateSearchText($pageBlock->content) : '';
                static::updateText($searchText, $pageBlock->block_id, $pageBlock->page_id, $pageBlock->language_id);
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