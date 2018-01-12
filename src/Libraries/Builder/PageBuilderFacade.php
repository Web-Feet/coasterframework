<?php
namespace CoasterCms\Libraries\Builder;

use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use Illuminate\Support\Facades\Facade;

/**
 * @see PageBuilder
 */
class PageBuilderFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pageBuilder';
    }

    protected static function getMockableClass() {
        return DefaultInstance::class;
    }
}
