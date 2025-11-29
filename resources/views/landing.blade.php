@extends('layouts.guest')

@section('title', 'Welcome to LabFix')

@section('content')
    <!-- Floating Elements -->
    <div class="floating-element floating-1"></div>
    <div class="floating-element floating-2"></div>
    <div class="floating-element floating-3"></div>

    <!-- Navigation -->
    <nav>
        <a href="{{ route('landing') }}" class="logo">LabFix</a>
        <div class="nav-content">            
            <div class="nav-links">
                <a href="#features" class="nav-link">Features</a>    
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
        </div>
        @auth
            <a href="{{ route('dashboard') }}" class="login-btn">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="login-btn">Sign In</a>
        @endauth
    </nav>

    <!-- Hero Section -->
    <section class="hero container">
        <div class="">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Streamline Your Computer Lab Management</h1>
                    <p>LabFix provides schools with an efficient solution to report, track, and resolve technical issues in computer labs. Minimize downtime and maximize learning opportunities.</p>
                    <div class="cta-buttons">
                        <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                        <a href="#features" class="btn-secondary">Learn More</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="dashboard-mockup">
                        <div class="mockup-header">
                            <div class="mockup-title">LabFix Dashboard</div>
                            <div class="status-badge">12 Active</div>
                        </div>
                        <div class="ticket-item">
                            <div class="ticket-title">#001 - Computer Lab A, PC-12</div>
                            <div class="ticket-meta">Status: In Progress • 2 hours ago</div>
                        </div>
                        <div class="ticket-item">
                            <div class="ticket-title">#002 - Computer Lab B, PC-05</div>
                            <div class="ticket-meta">Status: Assigned • 4 hours ago</div>
                        </div>
                        <div class="ticket-item">
                            <div class="ticket-title">#003 - Computer Lab C, Network Issue</div>
                            <div class="ticket-meta">Status: New • 6 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="feature-container">
            <h2>Why Choose LabFix?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Centralized Reporting</h3>
                    <p>Replace scattered emails and paper forms with one unified system for all technical issue reports.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👁️</div>
                    <h3>Real-Time Tracking</h3>
                    <p>Complete transparency from issue submission to resolution with live status updates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Smart Prioritization</h3>
                    <p>Automatically prioritize critical issues and assign them to the right technicians for faster resolution.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3>Automated Notifications</h3>
                    <p>Keep everyone informed with automatic email updates on new reports, progress, and resolutions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Knowledge Base</h3>
                    <p>Empower users with troubleshooting guides and FAQs to resolve common issues independently.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Analytics & Insights</h3>
                    <p>Track performance metrics and identify patterns to improve lab management and equipment planning.</p>
                </div>
            </div>
        </div>
    </section>
@endsection