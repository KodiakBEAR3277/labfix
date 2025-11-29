<nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
        <a href="{{ route('it.dashboard') }}" class="nav-link {{ request()->routeIs('it.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('it.tickets.index') }}" class="nav-link {{ request()->routeIs('it.tickets.index') ? 'active' : '' }}">Ticket Queue</a>
        <a href="{{ route('it.assignments') }}" class="nav-link {{ request()->routeIs('it.assignments') ? 'active' : '' }}">My Assignments</a>
        <a href="{{ route('it.knowledge-base') }}" class="nav-link {{ request()->routeIs('it.knowledge-base.*') ? 'active' : '' }}">Knowledge Base</a>
        <a href="{{ route('profile.show') }}" class="user-profile">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->first_name ?? 'I', 0, 1) . substr(auth()->user()->last_name ?? 'T', 0, 1)) }}</div>
            <span>{{ auth()->user()->first_name ?? 'IT' }} {{ auth()->user()->last_name ?? 'Support' }}</span>
        </a>
    </div>
</nav>