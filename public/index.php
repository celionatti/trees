<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/boot.php';

// Run the application
try {
    $app->run();
} catch (\Throwable $e) {
    // Global error handler
    http_response_code(500);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        // Show detailed error in debug mode
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Log error and show generic message in production
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<p>Something went wrong. Please try again later.</p>';
    }
}