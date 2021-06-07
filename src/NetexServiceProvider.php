<?php

namespace TromsFylkestrafikk\Netex;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Console\ImportStops;

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
                ImportStops::class,
            ]);
        }
    }
}
