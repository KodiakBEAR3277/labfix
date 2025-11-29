{{-- 
    Authentication Navigation
    Used by: Login and Register pages
    File: resources/views/partials/nav/auth.blade.php
--}}
<nav class="auth-nav">
    <a href="{{ route('landing') }}" class="auth-logo">
        <div class="auth-logo-icon"></div>
        <span>LabFix</span>
    </a>
    
    <div class="auth-nav-right">
        @if(request()->routeIs('login'))
            <span class="auth-nav-text">Don't have an account?</span>
            <a href="{{ route('register') }}" class="auth-signup-btn">Sign Up</a>
        @elseif(request()->routeIs('register'))
            <span class="auth-nav-text">Already have an account?</span>
            <a href="{{ route('login') }}" class="auth-signin-btn">Sign In</a>
        @else
            {{-- Default: Show both options --}}
            <a href="{{ route('login') }}" class="auth-signin-btn">Sign In</a>
            <a href="{{ route('register') }}" class="auth-signup-btn">Sign Up</a>
        @endif
    </div>
</nav>