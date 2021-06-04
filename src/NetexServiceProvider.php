<?php

namespace TromsFylkestrafikk\Netex;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class NetexServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '../database/migrations');
    }
}
