<?php namespace CoasterCms\Libraries\Blocks;

class Richtext extends Text
{

    public function edit($content)
    {
        return parent::edit(htmlentities($content));
    }

}