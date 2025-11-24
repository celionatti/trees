<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $resource;
    private ?string $uri;
    private string $mode;

    public function __construct(string $uri, string $mode = 'r')
    {
        $this->uri = $uri;
        $this->mode = $mode;
        $this->resource = fopen($uri, $mode);
    }

    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->resource) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $result = ftell($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return $this->resource ? feof($this->resource) : true;
    }

    public function isSeekable(): bool
    {
        return $this->resource ? $this->getMetadata('seekable') : false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        return strpos($mode, 'w') !== false || strpos($mode, '+') !== false || strpos($mode, 'a') !== false;
    }

    public function write($string): int
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        return strpos($mode, 'r') !== false || strpos($mode, '+') !== false;
    }

    public function read($length): string
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Unable to get stream contents');
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if ($this->resource === null) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}