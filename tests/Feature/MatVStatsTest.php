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

        // Run migrations
        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        // Clean up by dropping all package objects
        MatVStats::dropObjects();

        parent::tearDown();
    }

    public function test_can_initialize_stats(): void
    {
        // Create a test materialized view
        DB::unprepared("
            CREATE MATERIALIZED VIEW test_mv AS
            SELECT 1 as id
        ");

        $result = MatVStats::initializeStats();

        $this->assertNotEmpty($result);
        $this->assertTrue($result->contains('public.test_mv'));

        // Clean up
        DB::unprepared("DROP MATERIALIZED VIEW test_mv");
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

        // Clean up
        DB::unprepared("DROP MATERIALIZED VIEW test_mv");
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
        $this->assertTrue($result->contains('public.test_mv'));

        // Clean up
        DB::unprepared("DROP MATERIALIZED VIEW test_mv");
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

        // Clean up
        DB::unprepared("DROP MATERIALIZED VIEW test_mv");
    }
}