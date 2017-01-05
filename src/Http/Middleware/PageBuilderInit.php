<?php namespace CoasterCms\Http\Middleware;

use Closure;
use CoasterCms\Events\Cms\GeneratePage\InitializePageBuilder;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Libraries\Builder\PageBuilder\PageBuilderInstance;

class PageBuilderInit
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $pageLoader = [
            'class' => PageLoader::class,
            'args' => []
        ];
        $pageBuilder = [
            'class' => PageBuilderInstance::class,
            'args' => []
        ];

        // try to load cms page for current request
        event(new InitializePageBuilder($pageLoader, $pageBuilder));
        PageBuilder::setClass($pageBuilder['class'], $pageBuilder['args'], $pageLoader['class'], $pageLoader['args']);

        return $next($request);
    }

}

