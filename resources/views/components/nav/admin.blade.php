<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">User Management</a>
        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">System Settings</a>
        <a href="{{ route('admin.labs') }}" class="nav-link {{ request()->routeIs('admin.labs.*') ? 'active' : '' }}">Lab Configuration</a>
        <a href="{{ route('profile.show') }}" class="user-profile">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1) . substr(auth()->user()->last_name ?? 'D', 0, 1)) }}</div>
            <span>{{ auth()->user()->first_name ?? 'Admin' }} {{ auth()->user()->last_name ?? '' }}</span>
        </a>
    </div>
</nav>