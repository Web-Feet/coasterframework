<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockVideoCache;
use Illuminate\Support\Facades\View;

class Video extends _Base
{

    private static $_db_cache = [];

    public static function display($block, $block_data, $options = null)
    {
        if (!empty($block_data)) {
            if (empty(self::$_db_cache[$block_data])) {
                $cached_vid_info = BlockVideoCache::where('videoId', '=', $block_data)->first();
                if (!empty($cached_vid_info)) {
                    self::$_db_cache[$cached_vid_info->videoId] = unserialize($cached_vid_info->videoInfo);
                } else {
                    return 'Video data not found (try saving the page content again in the backend)';
                }
            }
            $video = self::$_db_cache[$block_data];
            $template = !empty($options['view']) ? $options['view'] : 'default';
            if (View::exists('themes.' . PageBuilder::$theme . '.blocks.videos.' . $template)) {
                return View::make('themes.' . PageBuilder::$theme . '.blocks.videos.' . $template, array('video' => $video))->render();
            } else {
                return 'Video template not found';
            }
        } else {
            return null;
        }
    }

    public static function save($block_content)
    {
        if (!empty($block_content)) {
            $data = self::dl('videos', array('id' => $block_content, 'part' => 'id,snippet'));
            if (!empty($data->items[0])) {
                $cached_video = BlockVideoCache::where('videoId', '=', $block_content)->first();
                if (empty($cached_video)) {
                    $new_video = new BlockVideoCache;
                    $new_video->videoId = $block_content;
                    $new_video->videoInfo = serialize($data->items[0]);
                    $new_video->save();
                } else {
                    $cached_video->videoInfo = serialize($data->items[0]);
                    $cached_video->save();
                }
            }
        }
        return $block_content;
    }

    public static function dl($request, $params = array(), $json = true)
    {
        // youtube api address and key
        $json_data = file_get_contents('https://www.googleapis.com/youtube/v3/' . $request . '?' . http_build_query(array_merge(array('key' => config('coaster::key.yt_server')), $params)));
        if (!$json_data) {
            throw new \Exception("Error fetching youtube video results");
        }
        if ($json) {
            $json_data = json_decode($json_data);
        }
        return $json_data;
    }

}