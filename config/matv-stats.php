<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection that should be used for materialized view stats.
    | This should be a PostgreSQL connection defined in your database config.
    |
    */
    'connection' => env('MATV_STATS_CONNECTION', env('DB_CONNECTION', 'pgsql')),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will log significant events like initialization
    | and errors to the Laravel log.
    |
    */
    'enable_logging' => env('MATV_STATS_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Throw Exceptions
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will throw exceptions for errors. When disabled,
    | errors will be logged (if logging is enabled) and operations will return
    | false or null as appropriate.
    |
    */
    'throw_exceptions' => env('MATV_STATS_THROW_EXCEPTIONS', true),
];
