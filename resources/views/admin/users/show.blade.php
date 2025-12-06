@extends('layouts.app')

@section('title', 'View User')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.users.index') }}" class="back-btn">← Back to User Management</a>

        <div class="page-header">
            <h1>View User</h1>
            <p>User details and activity information</p>
        </div>

        <div class="content-layout">
            <!-- Sidebar: User Profile -->
            <div>
                <div class="card">
                    <div class="user-avatar-large">{{ $user->initials }}</div>
                    <h2 class="user-name">{{ $user->full_name }}</h2>
                    <p class="user-email">{{ $user->email }}</p>
                    <span class="role-badge role-{{ $user->role }}">
                        {{ ucfirst(str_replace('-', ' ', $user->role)) }}
                    </span>

                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">User ID</span>
                            <span class="info-value">#{{ $user->id }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Joined</span>
                            <span class="info-value">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Active</span>
                            <span class="info-value">{{ $user->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->is_active ? 'success' : 'danger' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top: 1.5rem;">
                    <h3 class="card-title">Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary" style="text-align: center;">
                            Edit User
                        </a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" 
                                onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    Delete User
                                </button>
                            </form>
                        @else
                            <button class="btn btn-danger" style="width: 100%; opacity: 0.5;" disabled>
                                Cannot Delete Your Own Account
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main Content: User Details -->
            <div class="card">
                <h2 class="card-title">User Information</h2>

                <!-- Personal Information -->
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">First Name</span>
                            <span class="info-value">{{ $user->first_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Name</span>
                            <span class="info-value">{{ $user->last_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value">{{ $user->email }}</span>
                        </div>
                        @if($user->phone)
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value">{{ $user->phone }}</span>
                        </div>
                        @endif
                        @if($user->student_staff_id)
                        <div class="info-item">
                            <span class="info-label">Student/Staff ID</span>
                            <span class="info-value">{{ $user->student_staff_id }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Role & Permissions -->
                <div class="form-section">
                    <h3 class="section-title">Role & Permissions</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">User Role</span>
                            <span class="info-value">
                                <span class="role-badge role-{{ $user->role }}">
                                    {{ ucfirst(str_replace('-', ' ', $user->role)) }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Active</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->is_active ? 'success' : 'danger' }}">
                                    {{ $user->is_active ? 'Yes' : 'No' }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Notifications</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->email_notifications ? 'success' : 'secondary' }}">
                                    {{ $user->email_notifications ? 'Enabled' : 'Disabled' }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Can Submit Tickets</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->can_submit_tickets ? 'success' : 'secondary' }}">
                                    {{ $user->can_submit_tickets ? 'Yes' : 'No' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Account Timestamps -->
                <div class="form-section">
                    <h3 class="section-title">Account Timeline</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Account Created</span>
                            <span class="info-value">{{ $user->created_at->format('F j, Y \a\t g:i A') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value">{{ $user->updated_at->format('F j, Y \a\t g:i A') }}</span>
                        </div>
                        @if($user->email_verified_at)
                        <div class="info-item">
                            <span class="info-label">Email Verified</span>
                            <span class="info-value">{{ $user->email_verified_at->format('F j, Y \a\t g:i A') }}</span>
                        </div>
                        @else
                        <div class="info-item">
                            <span class="info-label">Email Verified</span>
                            <span class="info-value">
                                <span class="status-badge status-warning">Not Verified</span>
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection