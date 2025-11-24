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
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    private string $file;
    private ?string $clientFilename;
    private ?string $clientMediaType;
    private ?int $error;
    private ?int $size;
    private bool $moved = false;

    public function __construct(
        string $file,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        $this->file = $file;
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        return new Stream($this->file, 'r');
    }

    public function moveTo($targetPath): void
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file due to upload error');
        }

        if (!is_uploaded_file($this->file)) {
            throw new \RuntimeException('Specified file is not uploaded file');
        }

        if (!move_uploaded_file($this->file, $targetPath)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error ?? UPLOAD_ERR_OK;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}