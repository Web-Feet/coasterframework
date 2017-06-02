<?php
namespace CoasterCms\Libraries\Builder;

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
}
