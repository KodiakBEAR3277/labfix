@extends('layouts.app')

@section('title', 'Edit User')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.users.index') }}" class="back-btn">← Back to User Management</a>

        <div class="page-header">
            <h1>Edit User</h1>
            <p>Manage user information and permissions</p>
        </div>

        <div class="content-layout">
            <!-- Sidebar: User Profile -->
            <div>
                <div class="card">
                    <div class="user-avatar-large">JD</div>
                    <h2 class="user-name">John Doe</h2>
                    <p class="user-email">john.doe@email.com</p>
                    <span class="role-badge role-student">Student</span>

                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">User ID</span>
                            <span class="info-value">#12345</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Joined</span>
                            <span class="info-value">Jan 15, 2025</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Active</span>
                            <span class="info-value">2 hours ago</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tickets Created</span>
                            <span class="info-value">8</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top: 1.5rem;">
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="activity-log">
                        <div class="activity-item">
                            <div class="activity-date">Oct 2, 2025 - 2:30 PM</div>
                            <div class="activity-text">Submitted ticket #092</div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-date">Oct 1, 2025 - 10:15 AM</div>
                            <div class="activity-text">Logged in to system</div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-date">Sep 30, 2025 - 4:20 PM</div>
                            <div class="activity-text">Updated profile information</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Edit Form -->
            <div class="card">
                <h2 class="card-title">User Information</h2>

                <!-- Personal Information -->
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" value="John">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" value="Doe">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="john.doe@email.com">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number (Optional)</label>
                            <input type="tel" placeholder="+1 234 567 8900">
                        </div>
                        <div class="form-group">
                            <label>Student/Staff ID</label>
                            <input type="text" value="STU-2025-001">
                        </div>
                    </div>
                </div>

                <!-- Role & Permissions -->
                <div class="form-section">
                    <h3 class="section-title">Role & Permissions</h3>
                    <div class="form-group">
                        <label>User Role</label>
                        <select>
                            <option selected>Student</option>
                            <option>Staff</option>
                            <option>IT Support</option>
                            <option>Admin</option>
                        </select>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Account Active</span>
                        <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                            <div class="toggle-slider"></div>
                        </div>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Email Notifications</span>
                        <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                            <div class="toggle-slider"></div>
                        </div>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Can Submit Tickets</span>
                        <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                            <div class="toggle-slider"></div>
                        </div>
                    </div>
                </div>

                <!-- Password Reset -->
                <div class="form-section">
                    <h3 class="section-title">Security</h3>
                    <button class="btn btn-secondary" style="width: auto; padding: 0.8rem 1.5rem;">
                        Send Password Reset Email
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-primary">Save Changes</button>
                    <button class="btn btn-secondary">Cancel</button>
                    <button class="btn btn-danger">Delete User</button>
                </div>
            </div>
        </div>
    </div>
@endsection