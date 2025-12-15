<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('user.reports.create') }}" class="nav-link {{ request()->routeIs('user.reports.create') ? 'active' : '' }}">Report Issue</a>
        <a href="{{ route('user.reports.index') }}" class="nav-link {{ request()->routeIs('user.reports.*') && !request()->routeIs('user.reports.create') ? 'active' : '' }}">My Reports</a>
        <a href="{{ route('user.knowledge-base') }}" class="nav-link {{ request()->routeIs('user.knowledge-base') ? 'active' : '' }}">Knowledge Base</a>
        <a href="{{ route('user.lab-status') }}" class="nav-link {{ request()->routeIs('user.lab-status') ? 'active' : '' }}">Lab Status</a>
        <a href="{{ route('profile.show') }}" class="user-profile">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1) . substr(auth()->user()->last_name ?? 'S', 0, 1)) }}</div>
            <span>{{ auth()->user()->first_name ?? 'User' }} {{ auth()->user()->last_name ?? '' }}</span>
        </a>
    </div>
</nav>