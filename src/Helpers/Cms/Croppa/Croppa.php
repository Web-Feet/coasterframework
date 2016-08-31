<?php namespace CoasterCms\Helpers\Cms\Croppa;

use \Bkwld\Croppa\Helpers;
use URL;

class Croppa extends Helpers
{

    public function url($url, $width = null, $height = null, $options = null)
    {
        if (strpos($url, URL::to('/')) === 0) {
            $url = substr_replace($url, '', 0, strlen(URL::to('/')));
        }
        if (preg_match('#^/(' . config('coaster::frontend.croppa_handle') . ')$#', $url)) {
            $url = '/cache' . $url;
        }
        return parent::url($url, $width, $height, $options);
    }

}