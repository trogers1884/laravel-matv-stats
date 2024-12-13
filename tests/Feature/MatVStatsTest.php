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

        // Clean up any existing test view
        try {
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS test_mv");
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }

        // Run migrations to create package objects
        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        // Clean up test view
        try {
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS test_mv");
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }

        // Now it's safe to drop package objects since they exist
        try {
            MatVStats::dropObjects();
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }

        parent::tearDown();
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

    public function test_can_get_stats(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        MatVStats::initializeStats();
        $stats = MatVStats::getStats();

        $this->assertNotEmpty($stats);
        $this->assertNotNull($stats->firstWhere('mv_name', 'public.test_mv'));
    }

    public function test_can_reset_stats(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        MatVStats::initializeStats();
        $result = MatVStats::resetStats(['public.test_mv']);

        $this->assertNotEmpty($result);
        $this->assertTrue(in_array('public.test_mv', $result->toArray()));
    }

    public function test_can_get_stats_for_specific_view(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        MatVStats::initializeStats();
        $stats = MatVStats::getStatsForView('public.test_mv');

        $this->assertNotNull($stats);
        $this->assertEquals('public.test_mv', $stats->mv_name);
    }
}