<?php

require_once __DIR__ . '/env.php';

return [
    'driver' => 'mysql',
    'host' => busamas_env('DB_HOST', '127.0.0.1'),
    'port' => busamas_env('DB_PORT', '3306'),
    'database' => busamas_env('DB_DATABASE', 'busamas'),
    'username' => busamas_env('DB_USERNAME', 'root'),
    'password' => busamas_env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
