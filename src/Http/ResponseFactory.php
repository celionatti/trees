<?php

declare(strict_types=1);

namespace Trees\Http;

use Trees\Http\Message\Response;
use Trees\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    /**
     * Create a JSON response
     */
    public static function json($data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $body = new Stream(fopen('php://temp', 'w+'));
        $body->write(json_encode($data));
        $body->rewind();
        
        $headers['Content-Type'] = 'application/json';
        
        return new Response($statusCode, $body, $headers);
    }
    
    /**
     * Create a text response
     */
    public static function text(string $text, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $body = new Stream(fopen('php://temp', 'w+'));
        $body->write($text);
        $body->rewind();
        
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain';
        }
        
        return new Response($statusCode, $body, $headers);
    }
    
    /**
     * Create an HTML response
     */
    public static function html(string $html, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $body = new Stream(fopen('php://temp', 'w+'));
        $body->write($html);
        $body->rewind();
        
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        
        return new Response($statusCode, $body, $headers);
    }
    
    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): ResponseInterface
    {
        $body = new Stream(fopen('php://temp', 'w+'));
        
        return new Response($statusCode, $body, ['Location' => $url]);
    }
}