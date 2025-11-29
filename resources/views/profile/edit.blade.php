@extends('layouts.app')

@section('title', 'Edit Profile')

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
        <a href="{{ route('profile.show') }}" class="back-btn">← Back to Profile</a>

        <div class="page-header">
            <h1>Edit Profile</h1>
            <p>Update your personal information and preferences</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="content-layout">
            <!-- Sidebar -->
            <div>
                <div class="card">
                    <div class="user-avatar-large">{{ $user->initials }}</div>
                    <h2 class="user-name">{{ $user->full_name }}</h2>
                    <p class="user-email">{{ $user->email }}</p>
                    <span class="role-badge role-{{ $user->role }}">{{ ucfirst(str_replace('-', ' ', $user->role)) }}</span>
                </div>
            </div>

            <!-- Main Content: Edit Forms -->
            <div>
                <!-- Personal Information -->
                <div class="card">
                    <h2 class="card-title">Personal Information</h2>
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 234 567 8900">
                                    @error('phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Student/Staff ID</label>
                                    <input type="text" name="student_staff_id" value="{{ old('student_staff_id', $user->student_staff_id) }}" placeholder="e.g. STU-2025-001">
                                    @error('student_staff_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ route('profile.show') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="card" id="security" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Change Password</h2>
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                                @error('current_password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password" required>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <p class="help-text">Minimum 8 characters</p>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>

                <!-- Notification Preferences -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Notification Preferences</h2>
                    <form action="{{ route('profile.preferences.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive email updates about your tickets and system notifications</p>
                                </div>
                                <div class="toggle-switch {{ $user->email_notifications ? 'active' : '' }}" onclick="this.classList.toggle('active'); document.getElementById('email_notifications_input').checked = this.classList.contains('active');">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="checkbox" name="email_notifications" id="email_notifications_input" {{ $user->email_notifications ? 'checked' : '' }} style="display: none;">
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection