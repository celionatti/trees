<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees - Minimal Core (Everything is a Plugin)
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees;

use Trees\Router\Router;
use Trees\Http\Response;
use Trees\Http\ServerRequest;
use Trees\Container\Container;
use Trees\Plugins\HookManager;
use Trees\Plugins\PluginManager;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Trees\Contracts\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Trees
{
    private ContainerInterface $container;
    private PluginManager $pluginManager;
    private HookManager $hookManager;
    private Router $router;

    public function __construct(protected string $basePath)
    {
        // Initialize container
        $this->container = new Container();

        // Register core services
        $this->container->singleton('hook_manager', fn() => new HookManager());
        $this->container->singleton('router', fn() => new Router());

        $this->hookManager = $this->container->get('hook_manager');
        $this->router = $this->container->get('router');

        // Initialize plugin manager
        $this->pluginManager = new PluginManager(
            $this->container,
            $this->hookManager,
            $this->router,
            $basePath . '/plugins'
        );

        $this->container->singleton('plugin_manager', fn() => $this->pluginManager);
    }

    public function run(): void
    {
        // Discover plugins
        $this->pluginManager->discover();

        // Boot active plugins
        $this->pluginManager->bootPlugins();

        // Execute init action
        $this->hookManager->doAction('app.init', $this);
    }

    public function handle(?ServerRequestInterface $request = null): ResponseInterface
    {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        // Apply request filters
        $request = $this->hookManager->applyFilters('request.before', $request);

        // Match route
        $match = $this->router->match($request);

        if (!$match) {
            return $this->notFoundResponse();
        }

        // Execute handler
        $handler = $match['handler'];
        $params = $match['params'];

        if (is_callable($handler)) {
            $response = call_user_func($handler, $request, $params, $this->container);
        } elseif (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler);
            $controller = new $class($this->container);
            $response = $controller->$method($request, $params);
        } else {
            $response = $this->notFoundResponse();
        }

        // Apply response filters
        $response = $this->hookManager->applyFilters('response.after', $response);

        return $response;
    }

    private function notFoundResponse(): ResponseInterface
    {
        // Allow plugins to customize 404 page
        $html = $this->hookManager->applyFilters('404.content', $this->default404());
        return Response::html($html)->withStatus(404);
    }

    private function default404(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #020630ff 0%, #200333ff 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        h1 { font-size: 6em; margin: 0; }
        p { font-size: 1.5em; margin: 20px 0; }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: white;
            color: #020927ff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: transform 0.3s;
        }
        a:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Page Not Found</p>
        <a href="/">Go Home</a>
    </div>
</body>
</html>';
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getHookManager(): HookManager
    {
        return $this->hookManager;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }
}
