@extends('layouts.app')

@section('title', 'User Management')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p style="color: #9ca3af;">Manage user accounts, roles, and permissions</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Add New User</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Students</div>
                <div class="stat-value">{{ $stats['students'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Staff</div>
                <div class="stat-value">{{ $stats['staff'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">IT Support</div>
                <div class="stat-value">{{ $stats['it_support'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Admins</div>
                <div class="stat-value">{{ $stats['admins'] }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('admin.users.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search users by name or email..."
                        value="{{ request('search') }}"
                        onchange="document.getElementById('filterForm').submit()"
                    >
                </div>
                <div class="filter-tabs">
                    <button 
                        type="submit" 
                        name="role" 
                        value="all" 
                        class="tab {{ !request('role') || request('role') === 'all' ? 'active' : '' }}"
                    >
                        All Users
                    </button>
                    <button 
                        type="submit" 
                        name="role" 
                        value="student" 
                        class="tab {{ request('role') === 'student' ? 'active' : '' }}"
                    >
                        Students
                    </button>
                    <button 
                        type="submit" 
                        name="role" 
                        value="staff" 
                        class="tab {{ request('role') === 'staff' ? 'active' : '' }}"
                    >
                        Staff
                    </button>
                    <button 
                        type="submit" 
                        name="role" 
                        value="it-support" 
                        class="tab {{ request('role') === 'it-support' ? 'active' : '' }}"
                    >
                        IT Support
                    </button>
                    <button 
                        type="submit" 
                        name="role" 
                        value="admin" 
                        class="tab {{ request('role') === 'admin' ? 'active' : '' }}"
                    >
                        Admins
                    </button>
                </div>
            </div>
        </form>

        <!-- Display Active Filters -->
        @if(request('search') || (request('role') && request('role') !== 'all'))
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                <span style="color: #9ca3af; font-size: 0.9rem;">Active filters:</span>
                
                @if(request('search'))
                    <span class="badge" style="background: rgba(45, 212, 191, 0.2); color: #2dd4bf; padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                        Search: "{{ request('search') }}"
                        <a href="{{ route('admin.users.index', array_filter(['role' => request('role')])) }}" style="color: inherit; text-decoration: none; font-weight: bold;">×</a>
                    </span>
                @endif

                @if(request('role') && request('role') !== 'all')
                    <span class="badge" style="background: rgba(45, 212, 191, 0.2); color: #2dd4bf; padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                        Role: {{ ucfirst(str_replace('-', ' ', request('role'))) }}
                        <a href="{{ route('admin.users.index', array_filter(['search' => request('search')])) }}" style="color: inherit; text-decoration: none; font-weight: bold;">×</a>
                    </span>
                @endif

                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Clear All</a>
            </div>
        @endif

        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Last Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">{{ $user->initials }}</div>
                                    <div class="user-details">
                                        <h4>{{ $user->full_name }}</h4>
                                        <p>{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge role-{{ $user->role }}">{{ ucfirst(str_replace('-', ' ', $user->role)) }}</span></td>
                            <td><span class="status-badge status-{{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>{{ $user->updated_at->diffForHumans() }}</td>
                            <td>
                                <div class="action-menu">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="action-btn">Edit</a>
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="action-btn">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                @if(request('search') || request('role'))
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No users found</h3>
                                    <p>Try adjusting your filters or search terms</p>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary" style="margin-top: 1rem;">Clear Filters</a>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">👥</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No users yet</h3>
                                    <p>Get started by adding your first user</p>
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary" style="margin-top: 1rem;">+ Add User</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="pagination">
                    <div class="page-info">
                        Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                    </div>
                    <div class="page-controls">
                        {{-- Previous Page Link --}}
                        @if ($users->onFirstPage())
                            <button class="page-btn" disabled>← Previous</button>
                        @else
                            <a href="{{ $users->previousPageUrl() }}" class="page-btn">← Previous</a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($users->links()->elements[0] as $page => $url)
                            @if ($page == $users->currentPage())
                                <button class="page-btn active">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" class="page-btn">Next →</a>
                        @else
                            <button class="page-btn" disabled>Next →</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection