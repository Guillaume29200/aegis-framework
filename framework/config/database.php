<?php
declare(strict_types=1);

return [
    'type' => getenv('DB_TYPE') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'v4_classic',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
    
    'pool' => [
        'enabled' => false,
        'min_connections' => 2,
        'max_connections' => 10,
    ],
    
    'sqlite' => [
        'path' => __DIR__ . '/../../database.sqlite',
    ],
];
