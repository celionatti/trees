<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ $_SESSION['csrf_token'] ?? '' }}">
    <title>@yield('title', 'Trees Framework')</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-full bg-gray-50">

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

</body>

</html>