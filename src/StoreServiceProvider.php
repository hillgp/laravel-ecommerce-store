<?php

namespace LaravelEcommerce\Store;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelEcommerce\Store\Console\Commands\InstallCommand;
use LaravelEcommerce\Store\Console\Commands\SeedCommand;
use LaravelEcommerce\Store\Console\Commands\ProcessNotifications;
use LaravelEcommerce\Store\Console\Commands\OptimizePerformance;
use LaravelEcommerce\Store\Http\Middleware\StoreMiddleware;
use LaravelEcommerce\Store\Http\Middleware\AdminMiddleware;
use LaravelEcommerce\Store\Providers\EventServiceProvider;
use LaravelEcommerce\Store\Providers\RouteServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/store.php', 'store'
        );

        $this->app->singleton('store', function ($app) {
            return new StoreManager($app);
        });

        $this->app->alias('store', \LaravelEcommerce\Store\StoreManager::class);

        // Register facade in the container
        $this->app->singleton(\LaravelEcommerce\Store\Facades\Store::class, function ($app) {
            return $app['store'];
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerViews();
        $this->registerAssets();
        $this->registerCommands();
        $this->registerProviders();
        $this->registerMiddleware();
        $this->registerHelpers();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/store.php' => config_path('store.php'),
            ], 'store-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/store'),
            ], 'store-views');

            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/store'),
            ], 'store-assets');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('store.routes.prefix', 'store'),
            'namespace' => 'LaravelEcommerce\Store\Http\Controllers',
            'middleware' => config('store.routes.middleware', ['web']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * Register the package migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'store');
    }

    /**
     * Register the package assets.
     */
    protected function registerAssets(): void
    {
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/store'),
        ], 'store-assets');
    }

    /**
     * Register the package commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                \LaravelEcommerce\Store\Console\Commands\SeedCategoriesCommand::class,
                \LaravelEcommerce\Store\Console\Commands\ProcessNotifications::class,
                \LaravelEcommerce\Store\Console\Commands\OptimizePerformance::class,
                \LaravelEcommerce\Store\Console\Commands\ClearCacheCommand::class,
            ]);
        }
    }

    /**
     * Register additional service providers.
     */
    protected function registerProviders(): void
    {
        // $this->app->register(RouteServiceProvider::class);
        // $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register the package middleware.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('store', StoreMiddleware::class);
        $this->app['router']->aliasMiddleware('store.admin', AdminMiddleware::class);
    }

    /**
     * Register helper functions.
     */
    protected function registerHelpers(): void
    {
        foreach (glob(__DIR__.'/Helpers/*.php') as $file) {
            require_once $file;
        }
    }
}