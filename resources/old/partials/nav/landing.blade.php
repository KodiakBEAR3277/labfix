{{-- 
    Landing Page Navigation
    Used by: Public landing page
    File: resources/views/partials/nav/landing.blade.php
--}}
<nav>
    <div class="nav-content">
        <div class="logo">LabFix</div>
        
        <div class="nav-links">
            <a href="#features" class="nav-link">Features</a>
            <a href="#about" class="nav-link">About</a>
            <a href="#contact" class="nav-link">Contact</a>
        </div>
        
        <div>
            <a href="{{ route('login') }}" class="login-btn">Login</a>
        </div>
    </div>
</nav>