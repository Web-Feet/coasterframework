<?php

namespace CoasterCms\Helpers\Cms;

use Illuminate\Support\Str;

class StringHelper
{

    public static function cutString($string, $length = 200)
    {
        if (strlen($string) < $length) {
            return $string;
        } else {
            $str = Str::random(15);
            $string =  substr($string, 0, strpos(wordwrap($string, $length, "/$str/"), "/$str/")) . ' ...';
            return substr($string, -1) == '.' ? $string : $string . ' ...';
        }
    }
}
