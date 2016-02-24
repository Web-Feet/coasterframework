<?php namespace CoasterCms\Helpers;

class CroppaFacade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CoasterCms\Helpers\Croppa';
    }
}
