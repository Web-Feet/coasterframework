<?php namespace CoasterCms\Libraries\Blocks;

class Text extends String_
{

    public static function display($block, $block_data, $options = null)
    {
        $block_data = parent::display($block, $block_data, $options);
        if (empty($options['meta']) && empty($options['source'])) {
            $block_data = nl2br($block_data);
        }
        return $block_data;
    }

}