<?php

namespace Trogers1884\LaravelMatVStats\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Trogers1884\LaravelMatVStats\MatVStatsServiceProvider;
use Illuminate\Support\Facades\DB;

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
            'database' => env('DB_DATABASE', 'testing'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'postgres'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Drop all existing objects first
        $this->dropAllObjects();

        // Run migrations
        $this->artisan('migrate');
    }

    protected function dropAllObjects(): void
    {
        // Drop objects in correct order without using triggers
        $statements = [
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_start",
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_drop",
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_main",
            "DROP MATERIALIZED VIEW IF EXISTS test_mv",
            "DROP VIEW IF EXISTS public.tr1884_matvstats_v_stats",
            "DROP TABLE IF EXISTS public.tr1884_matvstats_t_stats CASCADE",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_start()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_drop()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_reset_stats(VARIADIC text[])",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_init()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_drop_objects()"
        ];

        foreach ($statements as $statement) {
            try {
                DB::unprepared($statement);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }
}