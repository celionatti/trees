<?php

declare(strict_types=1);

use Trees\Http\Router;

return function (Router $router) {
    
    // Home routes
    $router->get('/', 'App\Controllers\HomeController@index');
    $router->get('/about', 'App\Controllers\HomeController@about');
    
    // User routes with RESTful pattern
    $router->get('/users', 'App\Controllers\UserController@index');
    $router->get('/users/{id}', 'App\Controllers\UserController@show');
    $router->post('/users', 'App\Controllers\UserController@store');
    $router->put('/users/{id}', 'App\Controllers\UserController@update');
    $router->delete('/users/{id}', 'App\Controllers\UserController@destroy');
    
    // API routes with prefix
    $router->group(['prefix' => '/api'], function ($router) {
        $router->get('/health', function ($request) {
            return \Trees\Http\ResponseFactory::json([
                'status' => 'ok',
                'timestamp' => time(),
                'version' => '1.0.0'
            ]);
        });
        
        $router->group(['prefix' => '/v1'], function ($router) {
            $router->get('/users', 'App\Controllers\UserController@index');
            $router->get('/users/{id}', 'App\Controllers\UserController@show');
        });
    });
    
    // Route with closure and optional parameter
    $router->get('/hello/{name?}', function ($request) {
        $name = $request->getAttribute('name', 'Guest');
        return \Trees\Http\ResponseFactory::json(['message' => "Hello, {$name}!"]);
    });
};