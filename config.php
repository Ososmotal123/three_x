<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'database' => getenv('DB_NAME') ?: 'three_x',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'charset' => 'utf8mb4',
    ],
];
