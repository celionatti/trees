@extends('layouts.app')

@section('title')
Home Page
@endsection

@section('content')
<div class="px-4 py-8 sm:px-0">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            {{ $title }}
        </h1>

        <p class="text-lg text-gray-600 mb-6">
            {{ $message }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-blue-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-blue-900 mb-2">🔒 Secure</h3>
                <p class="text-blue-700">Built with security best practices.</p>
            </div>

            <div class="bg-green-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-green-900 mb-2">📈 Scalable</h3>
                <p class="text-green-700">Clean architecture with PSR standards.</p>
            </div>

            <div class="bg-purple-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-purple-900 mb-2">⚡ Modern</h3>
                <p class="text-purple-700">Tailwind CSS and modern PHP.</p>
            </div>
        </div>

        <div class="mt-8">
            <a href="/about" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                Learn More →
            </a>
        </div>
    </div>
</div>
@endsection