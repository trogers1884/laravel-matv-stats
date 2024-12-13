<?php

namespace Trogers1884\LaravelMatVStats;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use PDO;

class MatVStatsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/matv-stats.php', 'matv-stats'
        );

        $this->app->singleton('matv-stats', function ($app) {
            $connection = config('matv-stats.connection');

            // Verify PostgreSQL connection
            $db = $app->make(DatabaseManager::class);
            $pdo = $db->connection($connection)->getPdo();

            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
                throw new \RuntimeException('Laravel MatV Stats requires a PostgreSQL connection.');
            }

            return new MatVStats(
                $db,
                $connection,
                config('matv-stats.enable_logging', false),
                config('matv-stats.throw_exceptions', true)
            );
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish the config file
            $this->publishes([
                __DIR__.'/../config/matv-stats.php' => config_path('matv-stats.php'),
            ], 'matv-stats-config');

            // Load migrations
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Validate connection on boot if possible
        try {
            if (DB::connection(config('matv-stats.connection'))->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
                if (config('matv-stats.throw_exceptions', true)) {
                    throw new \RuntimeException('Laravel MatV Stats requires a PostgreSQL connection.');
                }

                if (config('matv-stats.enable_logging', false)) {
                    \Log::error('Laravel MatV Stats: Invalid database connection. PostgreSQL is required.');
                }
            }
        } catch (\Exception $e) {
            if (config('matv-stats.enable_logging', false)) {
                \Log::error('Laravel MatV Stats: ' . $e->getMessage());
            }

            if (config('matv-stats.throw_exceptions', true)) {
                throw $e;
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['matv-stats'];
    }
}