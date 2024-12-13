<?php

namespace Trogers1884\LaravelMatVStats\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trogers1884\LaravelMatVStats\MatVStatsServiceProvider;
use Illuminate\Support\Facades\Log;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MatVStatsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        Log::info("Running migrations");
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database configuration
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'testing'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'postgres'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);

        // Enable logging for debugging
        $app['config']->set('logging.default', 'stack');
        $app['config']->set('logging.channels.stack.channels', ['single']);
        $app['config']->set('logging.channels.single.path', 'php://stderr');
    }
}