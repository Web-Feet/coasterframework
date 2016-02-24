<?php namespace CoasterCms\Libraries\Blocks;

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
            $block_data = str_replace('%page_name%', PageBuilder::page_name(), $block_data);
            $block_data = str_replace('%site_name%', config('coaster::site.name'), $block_data);
            $block_data = htmlentities(strip_tags(html_entity_decode($block_data, ENT_QUOTES, 'UTF-8')));
            if (strlen($block_data) > 200) {
                $str = str_random(15);
                $block_data = substr($block_data, 0, strpos(wordwrap($block_data, 200, "/$str/"), "/$str/"));
                if (substr($block_data, -1) != '.') {
                    $block_data .= ' ...';
                }
            }

        }
        return $block_data;
    }

}