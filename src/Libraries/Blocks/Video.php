<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockVideoCache;
use GuzzleHttp\Client;
use View;

class Video extends String_
{
    /**
     * @var array
     */
    protected static $_cachedVideoData = [];

    /**
     * Display video using info from youtube API
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if (!empty($content)) {
            $template = !empty($options['view']) ? $options['view'] : 'default';
            $videoViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.videos.';
            if (!View::exists($videoViews . $template)) {
                return 'Video template not found';
            }
            if (!($videoInfo = $this->_cache($content))) {
                return 'Video does not exist, it may have been removed from youtube';
            }
            return View::make($videoViews . $template, ['video' => $videoInfo])->render();
        } else {
            return '';
        }
    }

    /**
     * Save video with and youtube video API data to cache
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if (!empty($postContent['select'])) {
            if ($videoData = $this->_dl('videos', ['id' => $postContent, 'part' => 'id,snippet'])) {
                $cachedVideoData = BlockVideoCache::where('videoId', '=', $postContent)->first() ?: new BlockVideoCache;
                $cachedVideoData->videoId = $postContent;
                $cachedVideoData->videoInfo = serialize($videoData);
                $cachedVideoData->save();
            }
        }
        return $this->save($postContent);
    }

    /**
     * Return cached youtube video API data or fetch if not cached
     * @param $videoId
     * @return mixed
     */
    protected function _cache($videoId)
    {
        if (!array_key_exists($videoId, static::$_cachedVideoData)) {
            if ($videoData = BlockVideoCache::where('videoId', '=', $videoId)->first()) {
                static::$_cachedVideoData[$videoId] = unserialize($videoData->videoInfo);
            } else {
                static::$_cachedVideoData[$videoId] = $this->_dl('videos', ['id' => $videoId, 'part' => 'id,snippet']);
            }
        }
        return static::$_cachedVideoData[$videoId];
    }

    /**
     * Download youtube API data
     * @param $request
     * @param array $params
     * @return null
     */
    protected function _dl($request, $params = [])
    {
        try {
            $youTube = new Client(['base_uri' => 'https://www.googleapis.com/youtube/v3/']);
            $params =  $params + ['key' => config('coaster::key.yt_server')];
            $response = $youTube->request('GET', $request, ['query' => $params]);
            $data = json_decode($response->getBody());
            return !empty($data) && !empty($data->items[0]) ? $data->items[0] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

}