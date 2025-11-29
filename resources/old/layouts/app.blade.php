{{-- 
    Main Application Layout
    Used by: User, IT, and Admin dashboards
    File: resources/views/layouts/app.blade.php
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - LabFix</title>
    
    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    
    {{-- Additional page-specific styles --}}
    @stack('styles')
</head>
<body>
    {{-- Navigation - Dynamic based on user role --}}
    @include('partials.nav.' . $navType)
    
    {{-- Main Content Area --}}
    <div class="container">
        {{-- Flash Messages (if any) --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page Content --}}
        @yield('content')
    </div>

    {{-- Additional page-specific scripts --}}
    @stack('scripts')
</body>
</html>