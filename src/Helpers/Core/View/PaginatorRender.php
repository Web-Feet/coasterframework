<?php namespace CoasterCms\Helpers\Core\View;

use Illuminate\Pagination\BootstrapThreePresenter;

class PaginatorRender
{

    public static function run($paginator, $bootstrap_version = null)
    {
        if (is_null($bootstrap_version)) {
            $bootstrap_version = config('coaster::frontend.bootstrap_version');
        }

        if ($bootstrap_version == 3 || empty($bootstrap_version)) {
            $default_presenter = new BootstrapThreePresenter($paginator);
        } elseif ($bootstrap_version == 2) {
            $default_presenter = new PaginatorRenderer\BootstrapTwoPresenter($paginator);
        } else {
            $default_presenter = new $bootstrap_version($paginator);
        }

        return $paginator->render($default_presenter);
    }

}