<?php

declare(strict_types=1);

use Trees\Http\Router;
use Trees\Http\ResponseFactory;
use Trees\Http\Middleware\CorsMiddleware;
use Trees\Http\Middleware\CsrfMiddleware;
use Trees\Http\Middleware\RoutingMiddleware;
use Trees\Http\Middleware\RateLimitMiddleware;
use Trees\Http\Middleware\SecurityHeadersMiddleware;

/**
 * Trees Framework - Booting Point
 * 
 * This file receives all requests and bootstraps the application
 */

// Define paths
define('ROOT_PATH', dirname(__DIR__));

// Load the autoloader
require ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

$whoops = new \Whoops\Run;
if ($_ENV['APP_ENV'] === 'local') {
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
} else {
    $whoops->pushHandler(function ($e) {
        echo 'An error occurred. Please try again later.';
        // Log the error details for further investigation
        error_log($e->getMessage());
    });
}

$whoops->register();

// Initialize the application
$app = new \Trees\Application();

// Container setup
$container = \Trees\Container\Container::getInstance();

// Load service providers
$serviceLoader = require ROOT_PATH . '/config/services.php';
$serviceLoader($container);

// Start the session with secure settings
$sessionConfig = config('security.session');
$dbConfig = config('database');

\Trees\Database\EloquentBootstrap::boot($dbConfig['connections'][$dbConfig['default']]);

session_set_cookie_params([
    'lifetime' => $sessionConfig['lifetime'] * 60,
    'path' => '/',
    'domain' => '',
    'secure' => $sessionConfig['secure'],
    'httponly' => $sessionConfig['httponly'],
    'samesite' => $sessionConfig['samesite'],
]);
session_start();

// Generate CSRF token if not present
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load security configuration
$securityConfig = config('security');

// Add security headers middleware
if (!empty($securityConfig['headers'])) {
    $app->pipe(new SecurityHeadersMiddleware($securityConfig['headers']));
}

// Add CORS middleware (if enabled)
if ($securityConfig['cors']['enabled'] ?? false) {
    $app->pipe(new CorsMiddleware(
        $securityConfig['cors']['allowed_origins'],
        $securityConfig['cors']['allowed_methods'],
        $securityConfig['cors']['allowed_headers'],
        $securityConfig['cors']['max_age']
    ));
}

// Add rate limiting middleware (if enabled)
if ($securityConfig['rate_limit']['enabled'] ?? false) {
    $app->pipe(new RateLimitMiddleware(
        $securityConfig['rate_limit']['max_requests'],
        $securityConfig['rate_limit']['per_minutes']
    ));
}

// Add CSRF protection middleware (if enabled)
if ($securityConfig['csrf']['enabled'] ?? false) {
    $app->pipe(new CsrfMiddleware($securityConfig['csrf']['except']));
}

// create Router and load routes
$router = new Router();
$routeLoader = require ROOT_PATH . '/routes/web.php';
$routeLoader($router);

// Add routing middleware
$app->pipe(new RoutingMiddleware($router, $container));

// Set fallback handler for 404 errors
$app->setFallback(function ($request) {
    $acceptHeader = $request->getHeaderLine('Accept');

    // Return JSON for API requests
    if (strpos($acceptHeader, 'application/json') !== false) {
        return ResponseFactory::json([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
            'path' => $request->getUri()->getPath(),
        ], 404);
    }

    // Return HTML for browser requests
    return ResponseFactory::html(
        '<html>
        <head>
            <style>
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
                    background-color: #000000;
                    color: #6b7280;
                }
                .container {
                    text-align: center;
                    max-width: 400px;
                    padding: 2rem;
                }
                .message {
                    font-size: 1.125rem;
                    line-height: 1.6;
                    margin: 1.5rem 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404 | Not Found</h1>
                <div class="message">
                    The page you are looking for might have been removed, 
                    had its name changed, or is temporarily unavailable.
                </div>
                <a href="/" style="color: #3b82f6; text-decoration: none;">Go to Homepage</a>
            </div>
        </body>
    </html>',
        404
    );
});

return $app;
