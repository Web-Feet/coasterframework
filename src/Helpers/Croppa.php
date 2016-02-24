<?php namespace CoasterCms\Helpers;

class Croppa extends \Bkwld\Croppa\Helpers
{

    public function url($url, $width = null, $height = null, $options = null)
    {
        if (preg_match('#^/(' . config('coaster::frontend.croppa_handle') . ')$#', $url)) {
            $url = '/cache' . $url;
        }
        return parent::url($url, $width, $height, $options);
    }

}