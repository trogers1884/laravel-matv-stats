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

        // Clean up any existing objects first
        $this->cleanDatabase();

        // Create objects in the correct order
        $statements = $this->getSetupStatements();
        foreach ($statements as $statement) {
            try {
                DB::unprepared($statement);
            } catch (\Exception $e) {
                // Log or handle the error
                throw $e;
            }
        }
    }

    protected function cleanDatabase(): void
    {
        $cleanup = [
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

        foreach ($cleanup as $statement) {
            try {
                DB::unprepared($statement);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    protected function getSetupStatements(): array
    {
        // Get the SQL statements from your migration file
        return [
            // Create base table first
            "CREATE TABLE IF NOT EXISTS public.tr1884_matvstats_t_stats
            (
                mv_name text COLLATE pg_catalog.\"default\",
                create_mv timestamp without time zone,
                mod_mv timestamp without time zone,
                refresh_mv_last timestamp without time zone,
                refresh_count integer DEFAULT 0,
                refresh_mv_time_last interval,
                refresh_mv_time_total interval DEFAULT '00:00:00'::interval,
                refresh_mv_time_min interval,
                refresh_mv_time_max interval,
                reset_last timestamp without time zone
            )",

            // Then create all functions
            // ... copy all the function creation statements from your migration ...

            // Create event triggers last
            // ... copy the event trigger creation statements from your migration ...
        ];
    }
}