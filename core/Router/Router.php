<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Router
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Router;

use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private array $routes = [];
    private array $pluginRoutes = [];

    /**
     * Register a GET route
     */
    public function get(string $path, $handler, ?string $pluginId = null): void
    {
        $this->addRoute('GET', $path, $handler, $pluginId);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, $handler, ?string $pluginId = null): void
    {
        $this->addRoute('POST', $path, $handler, $pluginId);
    }

    /**
     * Register any HTTP method route
     */
    public function addRoute(string $method, string $path, $handler, ?string $pluginId = null): void
    {
        $route = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->compilePattern($path)
        ];

        $this->routes[] = $route;

        if ($pluginId) {
            $this->pluginRoutes[$pluginId][] = count($this->routes) - 1;
        }
    }

    /**
     * Remove all routes for a specific plugin
     */
    public function removePluginRoutes(string $pluginId): void
    {
        if (!isset($this->pluginRoutes[$pluginId])) {
            return;
        }

        foreach ($this->pluginRoutes[$pluginId] as $index) {
            unset($this->routes[$index]);
        }

        unset($this->pluginRoutes[$pluginId]);
        $this->routes = array_values($this->routes);
    }

    /**
     * Match a request to a route
     */
    public function match(ServerRequestInterface $request): ?array
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // Remove full match
                return [
                    'handler' => $route['handler'],
                    'params' => $matches
                ];
            }
        }

        return null;
    }

    /**
     * Compile route pattern to regex
     */
    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
