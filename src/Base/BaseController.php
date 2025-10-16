<?php

declare(strict_types=1);

namespace Trees\Base;

use Trees\Image\Image;
use Trees\View\ViewEngine;
use Trees\Security\Validator;
use Trees\Database\Connection;
use Trees\Upload\FileUploader;
use Trees\Upload\UploadedFile;
use Trees\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Trees\Http\Message\ServerRequest;

abstract class BaseController
{
    protected $view;
    protected $db;

    public function __construct(ViewEngine $view, ?Connection $db = null)
    {
        $this->view = $view;
        $this->db = $db;

        // Share common data with views
        $this->view->share('app_name', config('app.name', 'Trees Framework'));
    }

    protected function view(string $view, array $data = []): ResponseInterface
    {
        try {
            $html = $this->view->render($view, $data);
            return ResponseFactory::html($html);
        } catch (\Throwable $e) {
            if (config('app.debug', false)) {
                throw $e;
            }

            return ResponseFactory::html(
                '<h1>View Error</h1><p>Unable to render view.</p>',
                500
            );
        }
    }

    protected function json($data, int $status = 200): ResponseInterface
    {
        return ResponseFactory::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): ResponseInterface
    {
        return ResponseFactory::redirect($url, $status);
    }

    protected function validate(ServerRequestInterface $request, array $rules): array
    {
        $data = $request->getParsedBody() ?? [];

        $validator = new Validator($data, $rules);

        if (!$validator->validate()) {
            throw new \RuntimeException(json_encode($validator->errors()), 422);
        }

        return $data;
    }

    protected function param(ServerRequestInterface $request, string $key, $default = null)
    {
        return $request->getAttribute($key, $default);
    }

    /**
     * Get uploaded file from request
     */
    protected function file(ServerRequest $request, string $key): ?UploadedFile
    {
        return $request->getUploadedFile($key);
    }

    /**
     * Upload file
     */
    protected function upload(UploadedFile $file, array $options = []): array
    {
        $uploader = new FileUploader();

        if (isset($options['path'])) {
            $uploader->setUploadPath($options['path']);
        }

        if (isset($options['allowed'])) {
            $uploader->setAllowedExtensions($options['allowed']);
        }

        if (isset($options['maxSize'])) {
            $uploader->setMaxSize($options['maxSize']);
        }

        return $uploader->upload($file, $options['name'] ?? null);
    }
}
