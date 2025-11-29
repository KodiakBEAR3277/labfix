@extends('layouts.app')

@section('title', 'My Profile')

@section('navigation')
    @if(auth()->user()->role === 'admin')
        <x-nav.admin />
    @elseif(auth()->user()->role === 'it-support')
        <x-nav.it />
    @else
        <x-nav.user />
    @endif
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>My Profile</h1>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <div class="content-layout">
            <!-- Sidebar: User Profile Card -->
            <div>
                <div class="card">
                    <div class="user-avatar-large">{{ $user->initials }}</div>
                    <h2 class="user-name">{{ $user->full_name }}</h2>
                    <p class="user-email">{{ $user->email }}</p>
                    <span class="role-badge role-{{ $user->role }}">{{ ucfirst(str_replace('-', ' ', $user->role)) }}</span>

                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">User ID</span>
                            <span class="info-value">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Member Since</span>
                            <span class="info-value">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value">{{ $user->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->is_active ? 'active' : 'inactive' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h3 class="card-title">Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="width: 100%;">Logout</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content: Profile Details -->
            <div>
                <!-- Personal Information -->
                <div class="card">
                    <h2 class="card-title">Personal Information</h2>
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
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value">{{ $user->phone ?? 'Not provided' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Student/Staff ID</span>
                            <span class="info-value">{{ $user->student_staff_id ?? 'Not provided' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Account Settings</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Role</span>
                            <span class="info-value">
                                <span class="role-badge role-{{ $user->role }}">{{ ucfirst(str_replace('-', ' ', $user->role)) }}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Notifications</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->email_notifications ? 'active' : 'inactive' }}">
                                    {{ $user->email_notifications ? 'Enabled' : 'Disabled' }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Can Submit Tickets</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->can_submit_tickets ? 'active' : 'inactive' }}">
                                    {{ $user->can_submit_tickets ? 'Yes' : 'No' }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Verified</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $user->email_verified_at ? 'active' : 'inactive' }}">
                                    {{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Security -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Security</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">
                        Keep your account secure by using a strong password and updating it regularly.
                    </p>
                    <a href="{{ route('profile.edit') }}#security" class="btn btn-secondary">Change Password</a>
                </div>
            </div>
        </div>
    </div>
@endsection