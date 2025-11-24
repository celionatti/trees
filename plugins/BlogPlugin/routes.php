<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* BlogPlugin - routes.php
* ----------------------------------------------
* @package Trees 2025
*/

use Trees\Http\Response;


// Add 'use ($pluginId, $view)' to make variables available
$router->get('/blog', function($request, $params, $container) use ($pluginId, $view) {
    echo "Plugin ID: " . $pluginId . "<br>";  // Should print: blog-plugin
    echo "View available: " . ($view ? 'Yes' : 'No') . "<br>";
    
    return Response::html('<h1>It works!</h1>');
}, $pluginId);

$router->get('/blog/{id}', function($request, $params, $container) {
    $id = $params[0] ?? 'unknown';
    return Response::html(
        "<h1>Blog Post #{$id}</h1><p>Content for post {$id}...</p>"
    );
}, $pluginId);