<?php

namespace CoasterCms\Facades;

use CoasterCms\Helpers\Cms\Install as InstallContract;
use Illuminate\Support\Facades\Facade;

/**
*
*/
class Install extends Facade
{

    protected static function getFacadeAccessor()
    {
        return InstallContract::class;
    }

    protected static function getMockableClass() {
        return self::getFacadeAccessor();
    }
}
