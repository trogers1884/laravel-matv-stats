<?php

namespace Trogers1884\LaravelMatVStats\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trogers1884\LaravelMatVStats\MatVStatsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MatVStatsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database configuration
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'postgres'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);

        // Setup package configuration
        $app['config']->set('matv-stats.connection', 'pgsql');
        $app['config']->set('matv-stats.enable_logging', true);
        $app['config']->set('matv-stats.throw_exceptions', true);
    }
}
