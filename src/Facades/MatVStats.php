<?php

namespace Trogers1884\LaravelMatVStats\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getStats()
 * @method static Collection initializeStats()
 * @method static Collection resetStats(?array $views = null)
 * @method static bool dropObjects()
 * @method static ?object getStatsForView(string $viewName)
 *
 * @see \Trogers1884\LaravelMatVStats\MatVStats
 */
class MatVStats extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'matv-stats';
    }
}
