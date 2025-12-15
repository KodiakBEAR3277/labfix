<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LabFix') - Streamline Your Computer Lab Management</title>
    
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @stack('styles')
</head>
<body class="@yield('body-class', '')">
    @yield('content')
    
    @stack('scripts')
</body>
</html> 