@extends('layouts.guest')

@section('title', 'Sign In')

@section('body-class', 'auth-page')

@section('content')
    <!-- Background Image -->
    <div class="auth-background"></div>

    <!-- Navigation -->
    <x-nav.auth 
        navText="Don't have an account?" 
        :navLink="route('register')" 
        navLinkText="Sign Up"
        navClass="auth-signup-btn"
    />

    <!-- Main Content -->
    <div class="auth-container">
        <!-- Left Side - Branding -->
        <div class="auth-left-section">
            <div class="auth-brand-logo">
                <div class="auth-brand-icon"></div>
                <h1 class="auth-brand-name">LabFix</h1>
            </div>
            <p class="auth-brand-tagline">Sign <span class="auth-highlight">in</span></p>
        </div>

        <!-- Right Side - Login Form -->
        <div class="auth-right-section">
            <div class="auth-login-container">
                <div class="auth-login-header">
                    <h2 class="auth-login-title">SIGN<span class="auth-highlight">IN</span></h2>
                    <p class="auth-login-subtitle">Sign in with email address</p>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div class="auth-form-group">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Yourname@gmail.com" 
                            value="{{ old('email') }}"
                            required
                            autofocus
                        >
                    </div>

                    <div class="auth-form-group">
                        <input 
                            type="password" 
                            name="password" 
                            placeholder="Password" 
                            required
                        >
                    </div>

                    <button type="submit" class="auth-submit-btn">Sign In</button>
                </form>

                <div class="auth-divider">Or continue with</div>

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
                    By registering you agree with our <a href="#">Terms and Conditions</a>
                </div>
            </div>
        </div>
    </div>
@endsection