<?php

namespace CoasterCms\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = new Application(__DIR__.'/testApp');

        $app->singleton(Kernel::class, ConsoleKernel::class);

        $app->singleton(HttpKernelContract::class, HttpKernel::class);
        $app->singleton(ExceptionHandler::class, Handler::class);
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
}
