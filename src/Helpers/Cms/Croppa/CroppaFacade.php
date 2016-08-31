<?php namespace CoasterCms\Helpers\Cms\Croppa;

use \Illuminate\Support\Facades\Facade;

class CroppaFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CoasterCms\Helpers\Cms\Croppa\Croppa';
    }
}
