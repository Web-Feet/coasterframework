<?php namespace CoasterCms\Helpers;

use \Illuminate\Support\Facades\Facade;

class CroppaFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CoasterCms\Helpers\Croppa';
    }
}
