# Laravel MatV Stats

A Laravel package for monitoring PostgreSQL materialized view refresh performance.

## Requirements

- PHP 8.1+
- Laravel 10.0+
- PostgreSQL database

## Installation

You can install the package via composer:

```bash
composer require trogers1884/laravel-matv-stats
```

After installing the package, you should run the migrations:

```bash
php artisan migrate
```

## Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="matv-stats-config"
```

## Usage

```php
use Trogers1884\LaravelMatVStats\Facades\MatVStats;

// Get stats for all materialized views
$stats = MatVStats::getStats();

// Initialize stats for existing materialized views
$initialized = MatVStats::initializeStats();

// Reset stats for specific views
$reset = MatVStats::resetStats(['schema.view_name']);

// Reset stats for all views
$resetAll = MatVStats::resetStats();

// Get stats for a specific view
$viewStats = MatVStats::getStatsForView('schema.view_name');
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.