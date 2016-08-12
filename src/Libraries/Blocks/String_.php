<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Libraries\Builder\PageBuilder;

class String_ extends _Base
{

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($options['meta'])) {
            $block_data = preg_replace_callback(
                '/{{\s*\$(?P<block>\w*)\s*}}/',
                function ($matches) {
                    return str_replace('"', "'", strip_tags(PageBuilder::block($matches['block'])));
                },
                $block_data
            );
            $block_data = trim(str_replace(PHP_EOL, ' ', $block_data));
            $block_data = htmlspecialchars(strip_tags(html_entity_decode($block_data, ENT_QUOTES, 'UTF-8')));
            $block_data = preg_replace('/\s+/', ' ', $block_data);
            $block_data = str_replace('%page_name%', PageBuilder::pageName(), $block_data);
            $block_data = str_replace('%site_name%', config('coaster::site.name'), $block_data);
            $block_data = htmlentities(strip_tags(html_entity_decode($block_data, ENT_QUOTES, 'UTF-8')));
            $block_data = StringHelper::cutString($block_data);
        }
        return $block_data;
    }

}