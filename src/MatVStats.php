<?php

namespace Trogers1884\LaravelMatVStats;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDOException;

class MatVStats
{
    /**
     * SQL object name constants
     */
    private const TABLE_NAME = 'public.tr1884_matvstats_t_stats';
    private const VIEW_NAME = 'public.tr1884_matvstats_v_stats';
    private const INIT_FUNCTION = 'public.tr1884_matvstats_fn_init';
    private const RESET_FUNCTION = 'public.tr1884_matvstats_fn_reset_stats';
    private const DROP_FUNCTION = 'public.tr1884_matvstats_fn_drop_objects';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly string $connection,
        private readonly bool $enableLogging = false,
        private readonly bool $throwExceptions = true
    ) {}

    /**
     * Get all materialized view statistics
     */
    public function getStats(): Collection
    {
        try {
            $result = $this->db->connection($this->connection)
                ->select("SELECT * FROM " . self::VIEW_NAME);

            return collect($result);
        } catch (PDOException $e) {
            $this->handleError('Failed to retrieve materialized view statistics', $e);
            return collect();
        }
    }

    /**
     * Initialize statistics for all existing materialized views
     */
    public function initializeStats(): Collection
    {
        try {
            $result = $this->db->connection($this->connection)
                ->select("SELECT " . self::INIT_FUNCTION . "()");

            $this->logMessage('Materialized view statistics initialized successfully');
            return collect($result);
        } catch (PDOException $e) {
            $this->handleError('Failed to initialize materialized view statistics', $e);
            return collect();
        }
    }

    /**
     * Reset statistics for specified materialized views or all if none specified
     */
    public function resetStats(?array $views = null): Collection
    {
        try {
            if ($views === null || empty($views)) {
                $result = $this->db->connection($this->connection)
                    ->select("SELECT " . self::RESET_FUNCTION . "('*')");
            } else {
                $viewList = implode(',', array_map(fn($view) => "'$view'", $views));
                $result = $this->db->connection($this->connection)
                    ->select("SELECT " . self::RESET_FUNCTION . "($viewList)");
            }

            $this->logMessage('Statistics reset successfully for ' . ($views ? implode(', ', $views) : 'all views'));
            return collect($result);
        } catch (PDOException $e) {
            $this->handleError('Failed to reset materialized view statistics', $e);
            return collect();
        }
    }

    /**
     * Drop all package objects from the database
     */
    public function dropObjects(): bool
    {
        try {
            $this->db->connection($this->connection)
                ->statement("SELECT " . self::DROP_FUNCTION . "()");

            $this->logMessage('All materialized view statistics objects dropped successfully');
            return true;
        } catch (PDOException $e) {
            $this->handleError('Failed to drop materialized view statistics objects', $e);
            return false;
        }
    }

    /**
     * Get statistics for a specific materialized view
     */
    public function getStatsForView(string $viewName): ?object
    {
        try {
            $result = $this->db->connection($this->connection)
                ->select("SELECT * FROM " . self::VIEW_NAME . " WHERE mv_name = ?", [$viewName]);

            return $result[0] ?? null;
        } catch (PDOException $e) {
            $this->handleError("Failed to retrieve statistics for view: $viewName", $e);
            return null;
        }
    }

    /**
     * Handle errors based on configuration
     */
    private function handleError(string $message, PDOException $exception): void
    {
        if ($this->enableLogging) {
            Log::error("Laravel MatV Stats: $message", [
                'error' => $exception->getMessage(),
                'code' => $exception->getCode()
            ]);
        }

        if ($this->throwExceptions) {
            throw new MatVStatsException($message, 0, $exception);
        }
    }

    /**
     * Log messages if logging is enabled
     */
    private function logMessage(string $message): void
    {
        if ($this->enableLogging) {
            Log::info("Laravel MatV Stats: $message");
        }
    }
}