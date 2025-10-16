<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Trees Framework',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    
    'key' => $_ENV['APP_KEY'] ?? null,
    
    'paths' => [
        'views' => ROOT_PATH . '/views',
        'cache' => ROOT_PATH . '/storage/cache',
        'logs' => ROOT_PATH . '/storage/logs',
        'storage' => ROOT_PATH . '/storage',
    ],
];