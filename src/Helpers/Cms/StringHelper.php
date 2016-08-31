<?php namespace CoasterCms\Helpers\Cms;

class StringHelper
{

    public static function cutString($string, $length = 200)
    {
        if (strlen($string) < $length) {
            return $string;
        } else {
            $str = str_random(15);
            $string =  substr($string, 0, strpos(wordwrap($string, $length, "/$str/"), "/$str/")) . ' ...';
            return substr($string, -1) == '.' ? $string : $string . ' ...';
        }
    }

}