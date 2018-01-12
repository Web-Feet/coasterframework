<?php

namespace CoasterCms\Facades;

use CoasterCms\Helpers\Cms\Install as InstallContract;
use Illuminate\Support\Facades\Facade;

/**
*
*/
class Install extends Facade
{

<<<<<<< HEAD
    /**
     * @return string
     */
=======
>>>>>>> Add test features to Coaster framework
    protected static function getFacadeAccessor()
    {
        return InstallContract::class;
    }

<<<<<<< HEAD
    /**
     * @return string
     */
    protected static function getMockableClass()
    {
=======
    protected static function getMockableClass() {
>>>>>>> Add test features to Coaster framework
        return self::getFacadeAccessor();
    }
}
