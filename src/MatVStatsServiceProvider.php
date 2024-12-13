<?php

namespace Trogers1884\LaravelMatVStats;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;

class MatVStatsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/matv-stats.php',
            'matv-stats'
        );

        $this->app->singleton('matv-stats', function ($app) {
            return new MatVStats(
                $app->make(DatabaseManager::class),
                config('matv-stats.connection'),
                config('matv-stats.enable_logging', false),
                config('matv-stats.throw_exceptions', true)
            );
        });
    }

    public function boot(): void
    {
        // Make sure migrations are loaded
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/matv-stats.php' => config_path('matv-stats.php'),
            ], 'matv-stats-config');
        }
    }
}
