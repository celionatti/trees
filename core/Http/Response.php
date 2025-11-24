<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Response
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Http;

use Trees\Http\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Response implements ResponseInterface
{
    private string $protocolVersion = '1.1';
    private array $headers = [];
    private StreamInterface $body;
    private int $statusCode;
    private string $reasonPhrase;

    private const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    public function __construct(
        int $status = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->statusCode = $status;
        $this->body = $body ?: new Stream('php://temp', 'wb+');
        $this->protocolVersion = $this->filterProtocolVersion($protocolVersion);
        $this->reasonPhrase = $reasonPhrase ?: (self::PHRASES[$status] ?? '');

        $this->setHeaders($headers);
    }

    public static function html(string $html, int $status = 200): self
    {
        $response = new self($status);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json($data, int $status = 200): self
    {
        $response = new self($status);
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function text(string $text, int $status = 200): self
    {
        $response = new self($status);
        $response->getBody()->write($text);
        return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self($status))->withHeader('Location', $url);
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

    // Response interface methods
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->statusCode = (int) $code;
        $new->reasonPhrase = $reasonPhrase ?: (self::PHRASES[$code] ?? '');
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}