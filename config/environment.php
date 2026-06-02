<?php
declare(strict_types=1);

return [
    'environment' => getenv('APP_ENV') ?: 'development',
    
    'development' => [
        'display_errors' => true,
        'error_reporting' => E_ALL,
        'debug_bar' => true,
        'log_queries' => true,
        'log_level' => 'DEBUG',
        'cache_enabled' => false,
        'minify_assets' => false,
        'show_deprecated' => true,
    ],
    
    'staging' => [
        'display_errors' => true,
        'error_reporting' => E_ALL & ~E_DEPRECATED,
        'debug_bar' => true,
        'log_queries' => true,
        'log_level' => 'INFO',
        'cache_enabled' => true,
        'minify_assets' => true,
        'show_deprecated' => false,
    ],
    
    'production' => [
        'display_errors' => false,
        'error_reporting' => 0,
        'debug_bar' => false,
        'log_queries' => false,
        'log_level' => 'ERROR',
        'cache_enabled' => true,
        'minify_assets' => true,
        'show_deprecated' => false,
    ],
];
