<?php

declare(strict_types=1);

namespace App\Controllers;

use Trees\Base\BaseController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends BaseController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        
        return $this->json(['users' => $users]);
    }
    
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->param($request, 'id');
        
        $user = [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        return $this->json(['user' => $user]);
    }
    
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        $user = [
            'id' => rand(1, 1000),
            'name' => $data['name'],
            'email' => $data['email'],
        ];
        
        return $this->json(['user' => $user, 'message' => 'User created successfully'], 201);
    }
    
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->param($request, 'id');
        
        $data = $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
        ]);
        
        $user = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
        ];
        
        return $this->json(['user' => $user, 'message' => 'User updated successfully']);
    }
    
    public function destroy(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->param($request, 'id');
        
        return $this->json(['message' => 'User deleted successfully']);
    }
}