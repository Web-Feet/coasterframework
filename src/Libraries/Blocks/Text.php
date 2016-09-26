<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;

class Text extends String_
{
    /**
     * Convert new lines to <br /> by default, also added length option which cuts string to a word nicely
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $content = parent::display($content, $options);
        if (!empty($options['source'])) {
            return $content;
        }
        if (empty($options['meta'])) {
            $content = nl2br($content);
        }
        if (!empty($options['length'])) {
            $content = StringHelper::cutString(strip_tags($content), $options['length']);
        }
        return $content;
    }

}