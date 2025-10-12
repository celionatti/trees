<?php

declare(strict_types=1);

namespace Trees\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Trees\Http\ResponseFactory;
use Trees\View\ViewEngine;
use Trees\Security\Validator;

abstract class BaseController
{
    protected $view;
    
    public function __construct()
    {
        $this->view = new ViewEngine(ROOT_PATH . '/views', ROOT_PATH . '/storage/views', true);
        
        $this->view->share('app_name', 'Trees Framework');
    }
    
    protected function view(string $view, array $data = []): ResponseInterface
    {
        $html = $this->view->render($view, $data);
        return ResponseFactory::html($html);
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