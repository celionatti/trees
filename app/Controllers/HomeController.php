<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Trees\Base\BaseController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends BaseController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view('home.index', [
            'title' => 'Welcome to Trees Framework',
            'message' => 'A secure, scalable PHP MVC framework'
        ]);
    }
    
    public function about(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view('home.about', [
            'title' => 'About Us'
        ]);
    }
}