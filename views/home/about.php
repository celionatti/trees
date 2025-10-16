@extends('layouts.app')

@section('title')
About - Trees Framework
@endsection

@section('content')
<div class="px-4 py-8 sm:px-0">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">About {{ $app_name }}</h1>
        
        <div class="prose max-w-none">
            <p class="text-gray-600 mb-4">
                Trees Framework is a lightweight, secure PHP MVC framework designed for building modern web applications.
            </p>
            
            <h2 class="text-2xl font-semibold text-gray-900 mt-6 mb-3">Features</h2>
            
            <ul class="list-disc list-inside space-y-2 text-gray-600">
                <li>PSR-7 HTTP message implementation</li>
                <li>PSR-15 Middleware support</li>
                <li>Advanced routing with parameters</li>
                <li>Blade-like template engine</li>
                <li>Built-in security features</li>
                <li>Input validation and sanitization</li>
                <li>Rate limiting</li>
                <li>RESTful API support</li>
            </ul>
        </div>
    </div>
</div>
@endsection