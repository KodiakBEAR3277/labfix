@extends('layouts.app')

@section('title', 'Edit User')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.users.show', $user->id) }}" class="back-btn">← Back to User Details</a>

        <div class="page-header">
            <h1>Edit User</h1>
            <p>Update user information and permissions</p>
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
                    </div>
                </div>

                <!-- Delete User Section -->
                <div class="card" style="margin-top: 1.5rem; border-color: rgba(239, 68, 68, 0.3);">
                    <h3 class="card-title" style="color: #ef4444;">Danger Zone</h3>
                    <p style="color: #9ca3af; margin-bottom: 1rem; font-size: 0.9rem;">
                        Once you delete this user, there is no going back. This will permanently delete the user and all their data.
                    </p>
                    @if($user->id === auth()->id())
                        <button class="btn btn-danger" style="width: 100%;" disabled>
                            Cannot Delete Your Own Account
                        </button>
                    @else
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete {{ $user->full_name }}? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="width: 100%;">
                                Delete User Permanently
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Main Content: Edit Form -->
            <div>
                <div class="card">
                    <h2 class="card-title">Edit User Information</h2>
                    
                    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3 class="section-title">Personal Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Student/Staff ID</label>
                                    <input type="text" name="student_staff_id" value="{{ old('student_staff_id', $user->student_staff_id) }}">
                                    @error('student_staff_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <!-- Role & Permissions -->
                        <div class="form-section">
                            <h3 class="section-title">Role & Permissions</h3>
                            <div class="form-group">
                                <label>User Role *</label>
                                <select name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="student" {{ old('role', $user->role) == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="it-support" {{ old('role', $user->role) == 'it-support' ? 'selected' : '' }}>IT Support</option>
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                                @error('role')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Account Active</h4>
                                    <p>Allow this user to log in to the system</p>
                                </div>
                                <div class="toggle-switch {{ $user->is_active ? 'active' : '' }}" onclick="toggleCheckbox(this, 'is_active')">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="checkbox" name="is_active" id="is_active" {{ $user->is_active ? 'checked' : '' }} style="display: none;">
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Email Notifications</h4>
                                    <p>Send email alerts for ticket updates</p>
                                </div>
                                <div class="toggle-switch {{ $user->email_notifications ? 'active' : '' }}" onclick="toggleCheckbox(this, 'email_notifications')">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="checkbox" name="email_notifications" id="email_notifications" {{ $user->email_notifications ? 'checked' : '' }} style="display: none;">
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Can Submit Tickets</h4>
                                    <p>Allow this user to create support tickets</p>
                                </div>
                                <div class="toggle-switch {{ $user->can_submit_tickets ? 'active' : '' }}" onclick="toggleCheckbox(this, 'can_submit_tickets')">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="checkbox" name="can_submit_tickets" id="can_submit_tickets" {{ $user->can_submit_tickets ? 'checked' : '' }} style="display: none;">
                            </div>
                        </div>

                        <!-- Password Change (Optional) -->
                        <div class="form-section">
                            <h3 class="section-title">Change Password (Optional)</h3>
                            <p style="color: #9ca3af; margin-bottom: 1rem; font-size: 0.9rem;">
                                Leave blank to keep the current password
                            </p>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password">
                                @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                                <p class="help-text">Minimum 8 characters</p>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="password_confirmation">
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleCheckbox(toggleElement, checkboxId) {
        toggleElement.classList.toggle('active');
        const checkbox = document.getElementById(checkboxId);
        checkbox.checked = toggleElement.classList.contains('active');
    }
</script>
@endpush