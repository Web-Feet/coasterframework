<?php
namespace CoasterCms\Libraries\Builder;

use Illuminate\Support\Facades\Facade;

/**
 * @see FormMessage
 */
class FormMessageFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'formMessage';
    }
}
