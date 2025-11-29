{{-- 
    Admin Navigation
    Used by: Admin dashboard and pages
    File: resources/views/partials/nav/admin.blade.php
--}}
<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('admin.dashboard') }}" 
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.users.index') }}" 
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            User Management
        </a>
        <a href="{{ route('admin.settings.index') }}" 
           class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            System Settings
        </a>
        <a href="{{ route('admin.labs') }}" 
           class="nav-link {{ request()->routeIs('admin.labs.*') ? 'active' : '' }}">
            Lab Configuration
        </a>
        
        {{-- User Profile with Avatar --}}
        <div class="user-profile">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
            </div>
            <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
        </div>
    </div>
</nav>