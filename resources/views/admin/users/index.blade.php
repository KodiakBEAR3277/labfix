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
        <div class="filter-bar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search users by name or email...">
            </div>
            <div class="filter-tabs">
                <button class="tab active">All Users</button>
                <button class="tab">Students</button>
                <button class="tab">Staff</button>
                <button class="tab">IT Support</button>
                <button class="tab">Admins</button>
            </div>
        </div>

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
                            <td><span class="role-badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
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
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                No users found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination">
                <div class="page-info">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users</div>
                <div class="page-controls">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection