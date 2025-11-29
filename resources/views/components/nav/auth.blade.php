<nav class="auth-nav">
    <a href="{{ route('landing') }}" class="auth-logo">
        <div class="auth-logo-icon"></div>
        <span>LabFix</span>
    </a>
    <div class="auth-nav-links">
        <a href="#features" class="auth-nav-link">Features</a>
        <a href="#about" class="auth-nav-link">About</a>
        <a href="#contact" class="auth-nav-link">Contact</a>
    </div>
    <div class="auth-nav-right">
        @auth
            <span class="auth-nav-text">Welcome back!</span>
            <a href="{{ route('dashboard') }}" class="auth-signup-btn">Dashboard</a>
        @else
            <span class="auth-nav-text">{{ $navText ?? "Don't have an account?" }}</span>
            <a href="{{ $navLink ?? route('register') }}" class="{{ $navClass ?? 'auth-signup-btn' }}">
                {{ $navLinkText ?? 'Sign Up' }}
            </a>
        @endauth
    </div>
</nav>