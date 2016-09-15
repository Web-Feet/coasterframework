<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;

class Text extends String_
{

    public function display($content, $options = [])
    {
        $content = parent::display($content);
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