<?php

namespace Trogers1884\LaravelMatVStats\Tests\Feature;

use Trogers1884\LaravelMatVStats\Tests\TestCase;
use Trogers1884\LaravelMatVStats\Facades\MatVStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatVStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Clean up test views
        DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS test_mv");

        parent::tearDown();
    }

    public function test_can_initialize_stats(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        // Verify the view exists
        $viewExists = DB::selectOne("
            SELECT EXISTS (
                SELECT FROM pg_matviews 
                WHERE schemaname = 'public' 
                AND matviewname = 'test_mv'
            ) as exists
        ");

        $this->assertTrue($viewExists->exists, "Test materialized view was not created");

        $result = MatVStats::initializeStats();
        Log::info("Initialize stats result: " . json_encode($result));

        $this->assertNotNull($result, "Stats initialization returned null");
        $this->assertNotEmpty($result->toArray(), "Stats initialization returned empty array");
        $this->assertContains('public.test_mv', $result->toArray(), "Stats should contain test_mv");
    }

    public function test_can_get_stats(): void
    {
        // Create test view and initialize
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        $result = MatVStats::initializeStats();
        $this->assertNotNull($result, "Failed to initialize stats");
        $this->assertNotEmpty($result->toArray(), "Initialization returned empty result");

        $stats = MatVStats::getStats();

        $this->assertNotEmpty($stats, "Stats should not be empty");
        $this->assertNotNull(
            $stats->firstWhere('mv_name', 'public.test_mv'),
            "Stats should contain test_mv"
        );
    }

    public function test_can_reset_stats(): void
    {
        // Create test view and initialize
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        $init = MatVStats::initializeStats();
        $this->assertNotNull($init, "Failed to initialize stats");
        $this->assertNotEmpty($init->toArray(), "Initialization returned empty result");

        // Reset stats for the test view
        $result = MatVStats::resetStats(['public.test_mv']);

        $this->assertNotNull($result, "Reset result should not be null");
        $this->assertNotEmpty($result->toArray(), "Reset result should not be empty");
        $this->assertContains('public.test_mv', $result->toArray(), "Reset result should contain test_mv");

        // Verify stats were reset
        $stats = MatVStats::getStatsForView('public.test_mv');
        $this->assertNotNull($stats, "Stats should exist after reset");
        $this->assertEquals(0, $stats->refresh_count, "Refresh count should be 0 after reset");
    }

    public function test_can_get_stats_for_specific_view(): void
    {
        // Create test view and initialize
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        $init = MatVStats::initializeStats();
        $this->assertNotNull($init, "Failed to initialize stats");
        $this->assertNotEmpty($init->toArray(), "Initialization returned empty result");

        $stats = MatVStats::getStatsForView('public.test_mv');

        $this->assertNotNull($stats, "Stats should not be null");
        $this->assertEquals('public.test_mv', $stats->mv_name, "Stats should be for test_mv");
    }
}