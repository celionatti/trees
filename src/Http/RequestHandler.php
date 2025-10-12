<?php

declare(strict_types=1);

namespace Trees\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    private $middleware = [];
    private $fallbackHandler;

    public function __construct(callable $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function pipe($middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If no middleware left, use fallback
        if (empty($this->middleware)) {
            return ($this->fallbackHandler)($request);
        }

        $middleware = array_shift($this->middleware);
        
        return $middleware->process($request, $this);
    }
}