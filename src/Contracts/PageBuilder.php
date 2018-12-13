<?php

namespace CoasterCms\Contracts;

use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use Illuminate\Support\Collection;

/**
 * Class PageBuilderLogger
 * @mixin DefaultInstance
 */
interface PageBuilder
{

    /**
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @return static
     */
    public function make($pageBuilderClass, $pageBuilderArgs);

    /**
     * @param string $name
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @return static
     */
    public function setInstance($name = null, $pageBuilderClass = '', $pageBuilderArgs = []);

    /**
     * @param string $name
     * @return static
     */
    public function getInstance($name = null);

    /**
     * @param string $key
     * @return Collection
     */
    public function logs($key = null);

    /**
     * @return bool
     */
    public function getLogState();

    /**
     * @param bool $state
     */
    public function setLogState($state);

}