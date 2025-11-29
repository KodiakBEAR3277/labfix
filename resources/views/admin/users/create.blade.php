@extends('layouts.app')

@section('title', 'Add New User')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.users.index') }}" class="back-btn">← Back to User Management</a>

        <div class="page-header">
            <h1>Add New User</h1>
            <p>Create a new user account</p>
        </div>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}">
                            @error('phone')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label>Student/Staff ID</label>
                            <input type="text" name="student_staff_id" value="{{ old('student_staff_id') }}">
                            @error('student_staff_id')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Account Settings</h3>
                    <div class="form-group">
                        <label>User Role *</label>
                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="it-support" {{ old('role') == 'it-support' ? 'selected' : '' }}>IT Support</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                        @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                        <p class="help-text">Minimum 8 characters</p>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection