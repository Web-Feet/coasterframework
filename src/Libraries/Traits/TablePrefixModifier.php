<?php namespace CoasterCms\Libraries\Traits;


trait TablePrefixModifier
{

    public $test = 1;

    public function getTable()
    {

        if (isset($this->table)) {


            return $this->table;
        }

        return '';
    }

}