<?php namespace CoasterCms\Helpers\Core\Croppa;

use \Illuminate\Support\Facades\Facade;

class CroppaFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CoasterCms\Helpers\Core\Croppa\Croppa';
    }
}
