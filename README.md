# Laravel Materialized View Statistics

A Laravel package for monitoring and analyzing PostgreSQL materialized view refresh performance.

## Requirements
- PHP ^8.1
- Laravel ^10.0|^11.0
- PostgreSQL database

## Installation
You can install the package via composer:

```bash
composer require trogers1884/laravel-matv-stats
```

The package will automatically register its service provider.

### Configuration
You may publish the config file with:
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

### Statistics Collected
- Creation time
- Last modification time
- Last refresh time
- Refresh count
- Last refresh duration
- Total refresh time
- Minimum refresh time
- Maximum refresh time
- Last reset time

## Uninstallation
To remove the package:

1. Remove the package using composer:
```bash
composer remove trogers1884/laravel-matv-stats
```

2. Clean up database objects:
```php
use Trogers1884\LaravelMatVStats\Facades\MatVStats;
MatVStats::dropObjects();
```

3. Remove the published configuration file if it exists:
```bash
rm config/matv-stats.php
```

## Testing
```bash
composer test
```

## Contributing
Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Code of Conduct
Please review our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

## Security
If you discover any security-related issues, please email trogers1884@gmail.com instead of using the issue tracker.

## Credits
- Tom Rogers (trogers1884 at gmail.com)
- Jeremy Gleed (jeremy_gleed at yahoo.com)

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.