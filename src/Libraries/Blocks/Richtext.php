<?php namespace CoasterCms\Libraries\Blocks;

class Richtext extends Text
{
    /**
     * Convert text to html entities (avoids code problems with text editor)
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        return parent::edit(htmlentities($content));
    }

}