<?php

declare(strict_types=1);

namespace Trees\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Trees\Http\ResponseFactory;
use Trees\Security\Validator;
use Trees\View\ViewEngine;

abstract class BaseController
{
    protected $view;
    
    public function __construct()
    {
        $viewPath = ROOT_PATH . '/views';
        $cachePath = ROOT_PATH . '/storage/views';
        
        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        
        $this->view = new ViewEngine($viewPath, $cachePath, false);
        $this->view->share('app_name', $_ENV['APP_NAME'] ?? 'Trees Framework');
    }
    
    protected function view(string $view, array $data = []): ResponseInterface
    {
        try {
            $html = $this->view->render($view, $data);
            return ResponseFactory::html($html);
        } catch (\Throwable $e) {
            // Better error handling
            if ($_ENV['APP_DEBUG'] ?? false) {
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
}