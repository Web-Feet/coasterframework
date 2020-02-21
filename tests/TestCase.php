<?php

namespace CoasterCms\Tests;

use CoasterCms\CmsServiceProvider;
use CoasterCms\Facades\Install;
use CoasterCms\Providers\CoasterRoutesProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Factory as Factory;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseMigrations;

    protected static $preloadedModels = [];

    protected $skipInstall = true;
    protected $response;

    public function setUp()
    {
        parent::setUp();
        if ($this->skipInstall) {
            Install::shouldReceive('isComplete')->andReturn(true);
        }

        $this->app->register(CmsServiceProvider::class);
        $this->app->register(CoasterRoutesProvider::class);
        $this->app['view']->addNamespace('coaster', realpath(__DIR__ . '/../resources/views/admin'));
        static::$preloadedModels = [];

        $this->registerDefaultMiddleware();
        $this->registerFactoriesSingleton();
        $this->disableExceptionHandling();
    }

    public function registerFactoriesSingleton()
    {
        $this->app->singleton(Factory::class, function ($app) {
            $faker = $app->make(\Faker\Generator::class);
            return Factory::construct($faker, realpath(__DIR__ . '/../database/factories'));
        });
    }

    public function registerDefaultMiddleware()
    {
        $this->app['router']->middlewareGroup('web', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    }

    public function runDatabaseMigrations()
    {
        $this->dropDBTables();

        $this->artisan('migrate', ['--path' => '../../database/migrations']);
    }

    public function dropDBTables()
    {
        $pdo = DB::getPdo();

        $tables = $pdo
            ->query("SHOW FULL TABLES;")
            ->fetchAll();

        $sql = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($tables as $tableInfo) {
            // truncate tables only
            if ('BASE TABLE' !== $tableInfo[1]) {
                continue;
            }

            $name = $tableInfo[0];
            $sql .= 'DROP TABLE ' . $name . ';';
        }

        $sql .= 'SET FOREIGN_KEY_CHECKS=1;';

        $pdo->exec($sql);
    }

    public static function preloadReset($model)
    {
        if (!in_array($model, static::$preloadedModels)) {
            static::$preloadedModels[] = $model;
            return true;
        }
        return false;
    }

    protected function disableExceptionHandling()
    {
        $this->oldExceptionHandler = $this->app->make(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, new class extends Handler
        {
            public function __construct()
            {
            }
            public function report(\Exception $e)
            {
            }
            public function render($request, \Exception $e)
            {
                throw $e;
            }
        }
        );
        return $this;
    }

    /**
     * Occasionally you do want exception handling to happen, because you're trying to test some behavior that depends on it,
     * like converting a ValidationException into a 422, or a ModelNotFoundException into a 404.
     *
     * So any time I need exception handling to actually work, I stick ->withExceptionHandling() before the HTTP request to turn
     * it back, effectively making it an opt-in behavior instead of opt-out like it would be by default.
     */
    protected function withExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, $this->oldExceptionHandler);
        return $this;
    }
}
