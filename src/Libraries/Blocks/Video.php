<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockVideoCache;
use GuzzleHttp\Client;
use View;

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
                    return 'Video data not found (try saving the page content again in the admin)';
                }
            }
            $video = self::$_db_cache[$block_data];
            $template = !empty($options['view']) ? $options['view'] : 'default';
            $videoViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.videos.';
            if (View::exists($videoViews . $template)) {
                return View::make($videoViews . $template, array('video' => $video))->render();
            } else {
                return 'Video template not found';
            }
        } else {
            return '';
        }
    }

    public static function save($block_content)
    {
        if (!empty($block_content)) {
            $videoData = self::dl('videos', array('id' => $block_content, 'part' => 'id,snippet'));
            if (!empty($videoData)) {
                $cached_video = BlockVideoCache::where('videoId', '=', $block_content)->first();
                if (empty($cached_video)) {
                    $new_video = new BlockVideoCache;
                    $new_video->videoId = $block_content;
                    $new_video->videoInfo = serialize($videoData);
                    $new_video->save();
                } else {
                    $cached_video->videoInfo = serialize($videoData);
                    $cached_video->save();
                }
            }
        }
        return $block_content;
    }

    public static function dl($request, $params = array())
    {
        // youtube api address and key
        try {
            $youTube = new Client(
                [
                    'base_uri' => 'https://www.googleapis.com/youtube/v3/'
                ]
            );
            $response = $youTube->request('GET', $request, ['query' => array_merge(array('key' => config('coaster::key.yt_server')), $params)]);
            $data = json_decode($response->getBody());
            if (!empty($data) && !empty($data->items[0])) {
                return $data->items[0];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

}