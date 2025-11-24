<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees;

use Trees\Router\Router;
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
        return new class implements ResponseInterface {
            private string $protocolVersion = '1.1';
            private array $headers = [];
            private StreamInterface $body;
            private int $statusCode = 404;
            private string $reasonPhrase = 'Not Found';

            public function __construct()
            {
                $this->body = new \Trees\Http\Stream('php://temp', 'wb+');
                $this->body->write('404 Not Found');
                $this->body->rewind();

                $this->headers['Content-Type'] = ['text/html; charset=UTF-8'];
                $this->headers['Content-Length'] = [(string) $this->body->getSize()];
            }

            public function getProtocolVersion(): string
            {
                return $this->protocolVersion;
            }

            public function withProtocolVersion($version): self
            {
                $new = clone $this;
                $new->protocolVersion = $version;
                return $new;
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function hasHeader($name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeader($name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaderLine($name): string
            {
                return implode(', ', $this->getHeader($name));
            }

            public function withHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];
                return $new;
            }

            public function withAddedHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = array_merge(
                    $new->headers[$name] ?? [],
                    is_array($value) ? $value : [$value]
                );
                return $new;
            }

            public function withoutHeader($name): self
            {
                $new = clone $this;
                unset($new->headers[$name]);
                return $new;
            }

            public function getBody(): StreamInterface
            {
                return $this->body;
            }

            public function withBody(StreamInterface $body): self
            {
                $new = clone $this;
                $new->body = $body;
                return $new;
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus($code, $reasonPhrase = ''): self
            {
                $new = clone $this;
                $new->statusCode = $code;
                $new->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);
                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->reasonPhrase;
            }

            private function getDefaultReasonPhrase(int $code): string
            {
                $phrases = [
                    404 => 'Not Found',
                ];
                return $phrases[$code] ?? '';
            }
        };
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
