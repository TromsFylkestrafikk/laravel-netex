<?php

namespace TromsFylkestrafikk\Netex;

use Illuminate\Support\ServiceProvider;
use TromsFylkestrafikk\Netex\Console\ActivationStatus;
use TromsFylkestrafikk\Netex\Console\ActivateRoutedata;
use TromsFylkestrafikk\Netex\Console\DeactivateRoutedata;
use TromsFylkestrafikk\Netex\Console\ImportStops;
use TromsFylkestrafikk\Netex\Console\ImportRouteData;

class NetexServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->setupMigrations();
        $this->setupConsoleCommands();
    }

    /**
     * Add necessary migrations for this package.
     */
    protected function setupMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup Artisan console commands.
     */
    protected function setupConsoleCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivationStatus::class,
                ActivateRoutedata::class,
                DeactivateRoutedata::class,
                ImportStops::class,
                ImportRouteData::class,
            ]);
        }
    }
}
