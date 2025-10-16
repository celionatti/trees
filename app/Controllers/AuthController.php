<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Trees\Base\BaseController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends BaseController
{
    public function showLoginForm(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view('auth.login', [
            'title' => 'Welcome to Trees Framework',
            'message' => 'A secure, scalable PHP MVC framework'
        ]);
    }
}