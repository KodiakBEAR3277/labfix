<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ \App\Models\Setting::get('system_name', 'LabFix') }}</title>
    
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @stack('styles')
</head>
<body>
    @yield('navigation')
    
    @yield('content')
    
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>