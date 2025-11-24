<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Http;

use Trees\Http\Stream;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest implements ServerRequestInterface
{
    private string $protocolVersion = '1.1';
    private array $headers = [];
    private StreamInterface $body;
    private string $method;
    private UriInterface $uri;
    private string $requestTarget;
    private array $serverParams;
    private array $cookieParams;
    private array $queryParams;
    private array $uploadedFiles;
    private $parsedBody;
    private array $attributes = [];

    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1',
        array $serverParams = []
    ) {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->body = $body ?: new Stream('php://temp', 'r+');
        $this->protocolVersion = $this->filterProtocolVersion($protocolVersion);
        $this->serverParams = $serverParams;
        $this->cookieParams = $_COOKIE;
        $this->queryParams = $_GET;
        $this->uploadedFiles = $this->normalizeUploadedFiles($_FILES);
        $this->parsedBody = $this->getDefaultParsedBody();

        $this->setHeaders($headers);
        $this->requestTarget = $this->uri->getPath() . 
            ($this->uri->getQuery() ? '?' . $this->uri->getQuery() : '');
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = Uri::fromGlobals();
        $headers = self::getHeadersFromGlobals();
        $body = new Stream('php://input', 'r');
        $serverParams = $_SERVER;

        return new self($method, $uri, $headers, $body, '1.1', $serverParams);
    }

    private static function getHeadersFromGlobals(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = [$value];
            }
        }
        return $headers;
    }

    private function filterMethod(string $method): string
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE'];
        $method = strtoupper($method);
        
        if (!in_array($method, $validMethods)) {
            throw new \InvalidArgumentException('Invalid HTTP method');
        }
        
        return $method;
    }

    private function filterProtocolVersion(string $version): string
    {
        $validVersions = ['1.0', '1.1', '2.0', '2'];
        if (!in_array($version, $validVersions)) {
            throw new \InvalidArgumentException('Invalid HTTP protocol version');
        }
        return $version;
    }

    private function setHeaders(array $headers): void
    {
        $this->headers = [];
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        }
    }

    private function getDefaultParsedBody()
    {
        if ($this->method === 'POST' && 
            isset($this->headers['content-type']) && 
            strpos($this->headers['content-type'][0], 'application/x-www-form-urlencoded') !== false) {
            return $_POST;
        }
        
        if (isset($this->headers['content-type']) && 
            strpos($this->headers['content-type'][0], 'application/json') !== false) {
            $content = $this->body->getContents();
            $this->body->rewind();
            return json_decode($content, true) ?: null;
        }
        
        return null;
    }

    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = new UploadedFile(
                    $value['tmp_name'],
                    $value['size'] ?? 0,
                    $value['error'] ?? UPLOAD_ERR_OK,
                    $value['name'] ?? '',
                    $value['type'] ?? ''
                );
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value);
            }
        }
        return $normalized;
    }

    // Message interface methods
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $new = clone $this;
        $new->protocolVersion = $this->filterProtocolVersion($version);
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        $name = strtolower($name);
        $new->headers[$name] = array_merge(
            $new->headers[$name] ?? [],
            is_array($value) ? $value : [$value]
        );
        return $new;
    }

    public function withoutHeader($name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
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

    // Request interface methods
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget): self
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $new = clone $this;
        $new->method = $this->filterMethod($method);
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost && $uri->getHost() !== '') {
            $new->headers['host'] = [$uri->getHost() . ($uri->getPort() ? ':' . $uri->getPort() : '')];
        }

        $new->requestTarget = $uri->getPath() . 
            ($uri->getQuery() ? '?' . $uri->getQuery() : '');

        return $new;
    }

    // ServerRequest interface methods
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}