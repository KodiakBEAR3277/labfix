{{-- 
    User Navigation
    Used by: User dashboard and pages
    File: resources/views/partials/nav/user.blade.php
--}}
<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('user.dashboard') }}" 
           class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('user.reports.create') }}" 
           class="nav-link {{ request()->routeIs('user.reports.create') ? 'active' : '' }}">
            Report Issue
        </a>
        <a href="{{ route('user.reports.index') }}" 
           class="nav-link {{ request()->routeIs('user.reports.index') || request()->routeIs('user.reports.show') ? 'active' : '' }}">
            My Reports
        </a>
        <a href="{{ route('user.kb.index') }}" 
           class="nav-link {{ request()->routeIs('user.kb.*') ? 'active' : '' }}">
            Knowledge Base
        </a>
        <a href="{{ route('user.labs.status') }}" 
           class="nav-link {{ request()->routeIs('user.labs.*') ? 'active' : '' }}">
            Lab Status
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