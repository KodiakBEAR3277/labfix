@extends('layouts.guest')

@section('title', 'Contact Us')

@section('content')
    <div class="contact-page">
        <div class="contact-container">
            <a href="{{ route('landing') }}" class="back-link">← Back to Home</a>
            
            <div class="contact-header">
                <h1>Get in Touch</h1>
                <p>Have questions? We're here to help!</p>
            </div>

            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">📧</div>
                    <h3>Email Us</h3>
                    <p>{{ \App\Models\Setting::get('support_email', 'support@labfix.edu') }}</p>
                    <a href="mailto:{{ \App\Models\Setting::get('support_email', 'support@labfix.edu') }}" class="contact-link">
                        Send Email
                    </a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <h3>Call Us</h3>
                    <p>{{ \App\Models\Setting::get('support_phone', '+63 123 456 7890') }}</p>
                    <a href="tel:{{ str_replace(' ', '', \App\Models\Setting::get('support_phone', '+63 123 456 7890')) }}" class="contact-link">
                        Call Now
                    </a>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <h3>Visit Us</h3>
                    <p>University IT Department<br>Main Campus Building</p>
                    <span class="contact-link" style="opacity: 0.5; cursor: default;">
                        Mon-Fri: 8:00 AM - 5:00 PM
                    </span>
                </div>
            </div>

            <div class="social-section">
                <h2>Connect With Us</h2>
                <div class="social-links">
                    <a href="#" class="social-btn" title="Facebook">
                        <span>📘</span>
                    </a>
                    <a href="#" class="social-btn" title="Twitter">
                        <span>🐦</span>
                    </a>
                    <a href="#" class="social-btn" title="Instagram">
                        <span>📷</span>
                    </a>
                    <a href="#" class="social-btn" title="LinkedIn">
                        <span>💼</span>
                    </a>
                </div>
            </div>

            <div class="quick-help">
                <h2>Need Quick Help?</h2>
                <p>Check out our knowledge base for common solutions</p>
                <a href="{{ route('user.knowledge-base') }}" class="btn-help">Browse Knowledge Base</a>
            </div>
        </div>
    </div>
@endsection