<?php namespace CoasterCms\Helpers\Cms\View;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatorRender
{
    /**
     * @param LengthAwarePaginator $paginator
     * @param int $bootstrapVersion
     * @return string
     */
    public static function run(LengthAwarePaginator $paginator, $bootstrapVersion = 0)
    {
        if (!$bootstrapVersion) {
            $bootstrapVersion = config('coaster::frontend.bootstrap_version');
        }
        return self::_render($paginator, $bootstrapVersion);
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return string
     */
    public static function admin(LengthAwarePaginator $paginator)
    {
        return self::_render($paginator, config('coaster::admin.bootstrap_version'));
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @param $bootstrapVersion
     * @return string
     */
    protected static function _render(LengthAwarePaginator $paginator, $bootstrapVersion)
    {
        if ($paginator->lastPage() <= 1) {
            return '';
        }
        switch ($bootstrapVersion) {
            case 4:
                $defaultPresenter = 'pagination::bootstrap-4';
                break;
            default:
                $defaultPresenter = '';
        }
        return $paginator->render($defaultPresenter);
    }

}