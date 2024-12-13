<?php

namespace Trogers1884\LaravelMatVStats;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDOException;
use Trogers1884\LaravelMatVStats\Exceptions\MatVStatsException;

class MatVStats
{
    private const VIEW_NAME = 'public.tr1884_matvstats_v_stats';
    private const INIT_FUNCTION = 'public.tr1884_matvstats_fn_init';
    private const RESET_FUNCTION = 'public.tr1884_matvstats_fn_reset_stats';
    private const DROP_FUNCTION = 'public.tr1884_matvstats_fn_drop_objects';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly string $connection = 'pgsql',
        private readonly bool $enableLogging = false,
        private readonly bool $throwExceptions = true
    ) {
    }

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

    public function initializeStats(): Collection
    {
        try {
            $result = $this->db->connection($this->connection)
                ->select("SELECT " . self::INIT_FUNCTION . "()");

            return collect($result)->pluck('tr1884_matvstats_fn_init');
        } catch (PDOException $e) {
            $this->handleError('Failed to initialize materialized view statistics', $e);

            return collect();
        }
    }

    public function resetStats(?array $views = null): Collection
    {
        try {
            if ($views === null || empty($views)) {
                $result = $this->db->connection($this->connection)
                    ->select("SELECT * FROM " . self::RESET_FUNCTION . "('*')");
            } else {
                $viewList = implode(',', array_map(fn ($view) => "'$view'", $views));
                $result = $this->db->connection($this->connection)
                    ->select("SELECT * FROM " . self::RESET_FUNCTION . "($viewList)");
            }

            return collect($result)->pluck('tr1884_matvstats_fn_reset_stats');
        } catch (PDOException $e) {
            $this->handleError('Failed to reset materialized view statistics', $e);

            return collect();
        }
    }

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

    private function handleError(string $message, PDOException $exception): void
    {
        if ($this->enableLogging) {
            Log::error("Laravel MatV Stats: $message", [
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);
        }

        if ($this->throwExceptions) {
            throw new MatVStatsException($message, 0, $exception);
        }
    }

    private function logMessage(string $message): void
    {
        if ($this->enableLogging) {
            Log::info("Laravel MatV Stats: $message");
        }
    }
}