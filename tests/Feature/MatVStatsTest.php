<?php

namespace Trogers1884\LaravelMatVStats\Tests\Feature;

use Trogers1884\LaravelMatVStats\Tests\TestCase;
use Trogers1884\LaravelMatVStats\Facades\MatVStats;
use Illuminate\Support\Facades\DB;

class MatVStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test views and package objects
        $this->cleanupTestView();
        DB::unprepared("SELECT public.tr1884_matvstats_fn_drop_objects()");

        // Run package migrations
        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        // Clean up
        $this->cleanupTestView();
        DB::unprepared("SELECT public.tr1884_matvstats_fn_drop_objects()");

        parent::tearDown();
    }

    protected function cleanupTestView(): void
    {
        try {
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS test_mv");
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }

    public function test_can_initialize_stats(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        // Ensure the view exists before initialization
        $this->assertTrue(
            DB::selectOne("
                SELECT EXISTS (
                    SELECT FROM pg_matviews 
                    WHERE matviewname = 'test_mv'
                )
            ")->exists
        );

        $result = MatVStats::initializeStats();

        $this->assertNotEmpty($result);
        $this->assertTrue(in_array('public.test_mv', $result->toArray()));
    }

    // ... rest of test methods remain the same ...
}