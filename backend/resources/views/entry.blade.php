{{--
    Vue Entry Point
    File: resources/views/entry.blade.php

    This is the single blade file that serves ALL Vue pages.
    Laravel routes point here, Vue takes over from the #app div.

    Your existing blade views are untouched — this coexists alongside them.
    To serve a Vue page from a route, just return this view:

        Route::get('/some-path', fn() => view('entry'))->name('some-name');

    To still serve a blade page, keep returning the blade view as normal:

        Route::get('/admin/tickets', [TicketController::class, 'index']);
        // ^ this still returns its blade view, no change needed
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LabFix') }}</title>

    {{--
        @vite() handles everything:
        - In dev (npm run dev): serves files from Vite's dev server with HMR
        - In production (npm run build): serves the bundled, minified, cache-busted file
        - Your main.css @import chain is automatically merged into one CSS file
          No extra config needed — pointing Vite at app.js is all it takes.
    --}}
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @vite(['resources/js/app.js'])
</head>
<body>
    {{--
        Vue mounts here. App.vue renders into this div.
        The rest of the page (nav, layout, content) is handled by Vue components.
    --}}
    <div id="app"></div>
</body>
</html>