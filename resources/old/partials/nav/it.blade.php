{{-- 
    IT Support Navigation
    Used by: IT dashboard and pages
    File: resources/views/partials/nav/it.blade.php
--}}
<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('it.dashboard') }}" 
           class="nav-link {{ request()->routeIs('it.dashboard') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('it.tickets.index') }}" 
           class="nav-link {{ request()->routeIs('it.tickets.*') ? 'active' : '' }}">
            Ticket Queue
        </a>
        <a href="{{ route('it.assignments.index') }}" 
           class="nav-link {{ request()->routeIs('it.assignments.*') ? 'active' : '' }}">
            My Assignments
        </a>
        <a href="{{ route('it.kb.admin') }}" 
           class="nav-link {{ request()->routeIs('it.kb.*') ? 'active' : '' }}">
            Knowledge Base
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