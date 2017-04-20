<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use View;

class String_ extends AbstractBlock
{

    /**
     * Frontend display for the block
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if (!empty($options['meta']) || !empty($options['pageBuilder'])) {
            $content = preg_replace_callback(
                '/{{\s*\$(?P<block>\w*)\s*}}/',
                function ($matches) {
                    return str_replace('"', "'", strip_tags(PageBuilder::block($matches['block'])));
                },
                $content
            );
            $content = str_replace('%page_name%', PageBuilder::pageName(), $content);
            $content = str_replace('%site_name%', config('coaster::site.name'), $content);
        }
        if (!empty($options['meta'])) {
            $content = trim(str_replace(PHP_EOL, ' ', $content));
            $content = preg_replace('/\s+/', ' ', $content);
            $content = htmlentities(strip_tags(html_entity_decode($content, ENT_QUOTES, 'UTF-8')));
            $content = StringHelper::cutString($content);
        }
        return parent::display($content, $options);
    }

}
