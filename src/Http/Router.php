<?php

declare(strict_types=1);

namespace Trees\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private $routes = [];
    private $groupPrefix = '';
    private $groupMiddleware = [];

    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function match(array $methods, string $path, $handler, array $middleware = []): self
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $middleware);
        }
        return $this;
    }

    public function any(string $path, $handler, array $middleware = []): self
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler, $middleware);
    }

    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, $attributes['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, $handler, array $middleware = []): self
    {
        $path = $this->groupPrefix . $path;
        $middleware = array_merge($this->groupMiddleware, $middleware);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $this->compileRoute($path),
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    private function compileRoute(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '(?P<$1>[^/]*)', $pattern);

        return '#^' . $pattern . '$#';
    }

    // public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    // {
    //     $method = $request->getMethod();
    //     $path = $request->getUri()->getPath();

    //     foreach ($this->routes as $route) {
    //         if ($route['method'] !== $method) {
    //             continue;
    //         }

    //         if (preg_match($route['pattern'], $path, $matches)) {
    //             $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

    //             foreach ($params as $key => $value) {
    //                 $request = $request->withAttribute($key, $value);
    //             }

    //             $request = $request->withAttribute('_route', $route);

    //             $handler = $route['handler'];

    //             if (is_string($handler) && strpos($handler, '@') !== false) {
    //                 [$controller, $method] = explode('@', $handler);

    //                 if (!class_exists($controller)) {
    //                     throw new \RuntimeException("Controller {$controller} not found");
    //                 }

    //                 $instance = new $controller();

    //                 if (!method_exists($instance, $method)) {
    //                     throw new \RuntimeException("Method {$method} not found in {$controller}");
    //                 }

    //                 return $instance->$method($request);
    //             } elseif (is_callable($handler)) {
    //                 return $handler($request);
    //             }

    //             throw new \RuntimeException('Invalid route handler');
    //         }
    //     }

    //     return null;
    // }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($params as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }

                $request = $request->withAttribute('_route', $route);

                $handler = $route['handler'];

                if (is_string($handler) && strpos($handler, '@') !== false) {
                    [$controller, $method] = explode('@', $handler);

                    if (!class_exists($controller)) {
                        throw new \RuntimeException("Controller {$controller} not found");
                    }

                    // Use container to resolve controller with dependencies
                    $container = \Trees\Container\Container::getInstance();
                    $instance = $container->make($controller);

                    if (!method_exists($instance, $method)) {
                        throw new \RuntimeException("Method {$method} not found in {$controller}");
                    }

                    return $instance->$method($request);
                } elseif (is_callable($handler)) {
                    return $handler($request);
                }

                throw new \RuntimeException('Invalid route handler');
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
