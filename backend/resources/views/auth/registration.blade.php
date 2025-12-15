@extends('layouts.guest')

@section('title', 'Sign Up')

@section('body-class', 'auth-page')

@section('content')
    <!-- Background Image -->
    <div class="auth-background"></div>

    <!-- Navigation -->
    <x-nav.auth 
        navText="Already have an Account?" 
        :navLink="route('login')" 
        navLinkText="Sign In"
        navClass="auth-signin-btn"
    />

    <!-- Main Content -->
    <div class="auth-container">
        <!-- Left Side - Branding -->
        <div class="auth-left-section">
            <div class="auth-brand-logo">
                <div class="auth-brand-icon"></div>
                <h1 class="auth-brand-name">LabFix</h1>
            </div>
            <p class="auth-brand-tagline">Sign <span class="auth-highlight">Up</span></p>
        </div>

        <!-- Right Side - Registration Form -->
        <div class="auth-right-section scrollable">
            <div class="auth-registration-container">
                <div class="auth-form-header">
                    <h2 class="auth-form-title">Create your account to get Started</h2>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register.post') }}" method="POST">
                    @csrf
                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="auth-form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="auth-form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="auth-form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" required>
                    </div>

                    <div class="auth-checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I have read the terms and conditions</label>
                    </div>

                    <button type="submit" class="auth-submit-btn">Create Account</button>
                </form>

                <div class="auth-divider">or continue with</div>

                <div class="auth-social-buttons">
                    <a href="#" class="auth-social-btn">
                        <div class="auth-social-icon auth-google-icon"></div>
                        Google
                    </a>
                    <a href="#" class="auth-social-btn">
                        <div class="auth-social-icon auth-facebook-icon"></div>
                        Facebook
                    </a>
                </div>

                <div class="auth-footer-text">
                    <p style="color: #9ca3af; font-size: 0.9rem; margin-top: 1rem; text-align: center;">
                        New accounts are created as <strong>Students</strong> by default.<br>
                        Contact an administrator if you need a different role.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection