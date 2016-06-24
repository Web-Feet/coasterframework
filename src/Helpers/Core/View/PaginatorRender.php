<?php namespace CoasterCms\Helpers\Core\View;

use Illuminate\Pagination\LengthAwarePaginator;

class PaginatorRender
{

    public static function run(LengthAwarePaginator $paginator, $bootStrapVersion = null)
    {
        if (is_null($bootStrapVersion)) {
            $bootStrapVersion = config('coaster::frontend.bootstrap_version');
        }

        switch ($bootStrapVersion) {
            default:
                $defaultPresenter = 'pagination::bootstrap-3';
        }

        return $paginator->render($defaultPresenter);
    }

}