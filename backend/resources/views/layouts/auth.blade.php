{{-- 
    Authentication Layout
    Used by: Login and Registration pages
    File: resources/views/layouts/auth.blade.php
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') - LabFix</title>
    
    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    
    {{-- Additional page-specific styles --}}
    @stack('styles')
</head>
<body class="auth-page">
    {{-- Auth-specific Navigation --}}
    @include('partials.nav.auth')
    
    {{-- Background --}}
    <div class="auth-background"></div>
    
    {{-- Main Content Area --}}
    @yield('content')

    {{-- Additional page-specific scripts --}}
    @stack('scripts')
</body>
</html>