<?php

declare(strict_types=1);

namespace Trees\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Trees\Http\Message\Response;

class RoutingMiddlewares implements MiddlewareInterface
{
    private $routes = [];

    public function route(string $path, callable $handler, string $method = 'GET'): self
    {
        $this->routes[] = [
            'path' => $path,
            'handler' => $handler,
            'method' => strtoupper($method)
        ];
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        foreach ($this->routes as $route) {
            if ($this->matchesRoute($route, $path, $method)) {
                return $route['handler']($request);
            }
        }

        // No route matched, pass to next middleware
        return $handler->handle($request);
    }

    private function matchesRoute(array $route, string $path, string $method): bool
    {
        return $route['method'] === $method && $route['path'] === $path;
    }
}