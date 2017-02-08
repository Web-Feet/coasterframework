<?php namespace CoasterCms\Libraries\Blocks;

class Richtext extends Text
{

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $options['nl2br'] = 0; // remove <br />
        return parent::display($content, $options);
    }

}