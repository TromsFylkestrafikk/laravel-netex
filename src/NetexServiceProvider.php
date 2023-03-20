<?php

namespace TromsFylkestrafikk\Netex;

use Illuminate\Support\ServiceProvider;
use TromsFylkestrafikk\Netex\Console\ImportStops;
use TromsFylkestrafikk\Netex\Console\RoutedataActivate;
use TromsFylkestrafikk\Netex\Console\RoutedataDeactivate;
use TromsFylkestrafikk\Netex\Console\RoutedataImport;
use TromsFylkestrafikk\Netex\Console\RoutedataList;
use TromsFylkestrafikk\Netex\Console\RoutedataRemove;
use TromsFylkestrafikk\Netex\Console\RoutedataStatus;
use TromsFylkestrafikk\Netex\Console\SyncActiveStops;
use TromsFylkestrafikk\Netex\Services\StopsActivator;

class NetexServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishConfig();
        $this->setupMigrations();
        $this->setupConsoleCommands();
    }

    public function register()
    {
        $this->bindServices();
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
    protected function setupMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function bindServices()
    {
        $this->app->singleton(StopsActivator::class, function () {
            return new StopsActivator();
        });
    }

    /**
     * Setup Artisan console commands.
     */
    protected function setupConsoleCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportStops::class,
                RoutedataActivate::class,
                RoutedataDeactivate::class,
                RoutedataImport::class,
                RoutedataList::class,
                RoutedataRemove::class,
                RoutedataStatus::class,
                SyncActiveStops::class,
            ]);
        }
    }
}
