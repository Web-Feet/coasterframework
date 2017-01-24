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

    /**
     * Convert text to html entities (avoids code problems with text editor)
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        return parent::edit($content);
    }

}