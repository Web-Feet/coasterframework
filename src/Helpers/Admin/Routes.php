<?php namespace CoasterCms\Helpers\Admin;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class Routes
{

    public static function jsonRoutes()
    {
        $coasterRoutes = [];

        /** @var Router $declaredRoutes */
        $declaredRoutes = app('router');

        foreach($declaredRoutes->getRoutes() as $route) {
            /** @var Route $route */
            $action = $route->getAction();
            if (!empty($action['as']) && strpos($action['as'], 'coaster.admin') === 0) {
                $coasterRoutes[$action['as']] = $route->getUri();

            }
        }
        
        return json_encode($coasterRoutes);
    }

}