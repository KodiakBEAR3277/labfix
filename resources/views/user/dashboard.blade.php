@extends('layouts.app')

@section('title', 'User Dashboard')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, John!</h1>
            <p>Here's what's happening with your lab reports</p>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Active Reports</div>
                <div class="stat-value">3</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Resolved This Month</div>
                <div class="stat-value">8</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Resolution Time</div>
                <div class="stat-value">2.5<span style="font-size: 1rem; color: #9ca3af;"> hrs</span></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="{{ route('user.reports.create') }}" class="action-card">
                <div class="action-icon">📝</div>
                <h3>Report New Issue</h3>
                <p>Submit a new technical problem</p>
            </a>
            <a href="{{ route('user.reports.index') }}" class="action-card">
                <div class="action-icon">📊</div>
                <h3>View All Reports</h3>
                <p>Track your submission history</p>
            </a>
            <a href="{{ route('user.knowledge-base') }}" class="action-card">
                <div class="action-icon">📚</div>
                <h3>Knowledge Base</h3>
                <p>Find solutions to common issues</p>
            </a>
            <a href="{{ route('user.lab-status') }}" class="action-card">
                <div class="action-icon">💻</div>
                <h3>Lab Status</h3>
                <p>Check lab availability</p>
            </a>
        </div>

        <!-- Recent Reports -->
        <div class="section-header">
            <h2>My Recent Reports</h2>
            <button class="filter-btn">Filter ▼</button>
        </div>

        <div class="reports-grid">
            <div class="report-card">
                <div class="report-header">
                    <div>
                        <div class="report-title">Computer won't start</div>
                        <div class="report-id">#001</div>
                    </div>
                    <div class="status-badge status-progress">In Progress</div>
                </div>
                <div class="report-meta">
                    <div class="report-meta-item">
                        <span>📍</span> Computer Lab A, PC-12
                    </div>
                    <div class="report-meta-item">
                        <span>🕒</span> 2 hours ago
                    </div>
                </div>
                <div class="report-description">
                    The computer shows a black screen when powered on. No POST beep sound detected.
                </div>
            </div>

            <div class="report-card">
                <div class="report-header">
                    <div>
                        <div class="report-title">Keyboard keys not working</div>
                        <div class="report-id">#002</div>
                    </div>
                    <div class="status-badge status-progress">In Progress</div>
                </div>
                <div class="report-meta">
                    <div class="report-meta-item">
                        <span>📍</span> Computer Lab B, PC-05
                    </div>
                    <div class="report-meta-item">
                        <span>🕒</span> Yesterday
                    </div>
                </div>
                <div class="report-description">
                    Several keys on the keyboard are unresponsive (A, S, D, F keys).
                </div>
            </div>

            <div class="report-card">
                <div class="report-header">
                    <div>
                        <div class="report-title">Software installation error</div>
                        <div class="report-id">#003</div>
                    </div>
                    <div class="status-badge status-resolved">Resolved</div>
                </div>
                <div class="report-meta">
                    <div class="report-meta-item">
                        <span>📍</span> Computer Lab C, PC-20
                    </div>
                    <div class="report-meta-item">
                        <span>🕒</span> 3 days ago
                    </div>
                </div>
                <div class="report-description">
                    Unable to install Visual Studio Code. Error message: "Installation failed."
                </div>
            </div>
        </div>
    </div>
@endsection