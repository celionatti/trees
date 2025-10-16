<?php

use Trees\Container\Container;
use Trees\Database\Connection;
use Trees\View\ViewEngine;

return function (Container $container) {

    // Bind ViewEngine as singleton
    $container->singleton(ViewEngine::class, function ($container) {
        $viewPath = ROOT_PATH . '/views';
        $cachePath = ROOT_PATH . '/storage/views';

        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        return new ViewEngine(
            $viewPath,
            $cachePath,
            !config('app.debug', false)
        );
    });

    // Bind Database Connection as singleton
    $container->singleton(Connection::class, function ($container) {
        $defaultConnection = config('database.default');
        $dbConfig = config("database.connections.{$defaultConnection}");

        return Connection::getInstance($dbConfig);
    });

    // Optional: Create aliases for easier access
    $container->alias(ViewEngine::class, 'view');
    $container->alias(Connection::class, 'db');
};
