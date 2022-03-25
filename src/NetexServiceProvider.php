<?php

namespace TromsFylkestrafikk\Netex;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Netex\Console\ActivateRoutedata;
use TromsFylkestrafikk\Netex\Console\DeactivateRoutedata;
use TromsFylkestrafikk\Netex\Console\ImportStops;
use TromsFylkestrafikk\Netex\Console\ImportRouteData;

class NetexServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishConfig();
        $this->registerMigrations();
        $this->registerConsoleCommands();
        $this->registerRoutes();
    }

    protected function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/netex.php' => config_path('netex.php'),
            ], ['netex', 'config', 'netex-config']);
        }
    }

    /**
     * Add necessary migrations for this package.
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup Artisan console commands.
     */
    protected function registerConsoleCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivateRoutedata::class,
                DeactivateRoutedata::class,
                ImportStops::class,
                ImportRouteData::class,
            ]);
        }
    }

    protected function registerRoutes()
    {
        $routeConf = config('netex.routes_api');
        if ($routeConf) {
            Route::group(config('netex.routes_api'), function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
        }
    }
}
