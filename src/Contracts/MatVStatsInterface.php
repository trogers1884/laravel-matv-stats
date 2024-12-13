<?php

namespace Trogers1884\LaravelMatVStats\Contracts;

use Illuminate\Support\Collection;

interface MatVStatsInterface
{
    /**
     * Get all materialized view statistics
     */
    public function getStats(): Collection;

    /**
     * Initialize statistics for all existing materialized views
     */
    public function initializeStats(): Collection;

    /**
     * Reset statistics for specified materialized views or all if none specified
     */
    public function resetStats(?array $views = null): Collection;

    /**
     * Drop all package objects from the database
     */
    public function dropObjects(): bool;

    /**
     * Get statistics for a specific materialized view
     */
    public function getStatsForView(string $viewName): ?object;
}
