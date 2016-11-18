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
        if (isset($options['meta']) &&  !isset($options['nl2br'])) {
            $options['nl2br'] = $options['meta'];
        }
        if ((isset($options['nl2br']) && $options['nl2br']) || !isset($options['nl2br'])) {
            $content = nl2br($content);
        }
        if (!empty($options['length'])) {
            $content = StringHelper::cutString(strip_tags($content), $options['length']);
        }
        return $content;
    }

}