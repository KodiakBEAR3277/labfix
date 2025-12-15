{{-- 
    Landing Page Layout
    Used by: Public landing page
    File: resources/views/layouts/landing.blade.php
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Welcome') - LabFix</title>
    
    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    
    {{-- Additional page-specific styles --}}
    @stack('styles')
</head>
<body>
    {{-- Landing Navigation --}}
    @include('partials.nav.landing')
    
    {{-- Main Content Area --}}
    @yield('content')

    {{-- Footer (if needed) --}}
    @yield('footer')

    {{-- Additional page-specific scripts --}}
    @stack('scripts')
</body>
</html>