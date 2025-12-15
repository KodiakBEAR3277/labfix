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
            <h1>Welcome back, {{ auth()->user()->first_name }}!</h1>
            <p>Here's what's happening with your lab reports</p>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Active Reports</div>
                <div class="stat-value">{{ $stats['active'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Resolved This Month</div>
                <div class="stat-value">{{ $stats['resolved_this_month'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Resolution Time</div>
                <div class="stat-value">{{ $stats['avg_resolution_time'] }}<span style="font-size: 1rem; color: #9ca3af;"> hrs</span></div>
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
            <a href="{{ route('user.reports.index') }}" class="view-all">View All →</a>
        </div>

        <div class="reports-grid">
            @forelse($reports as $report)
                <div class="report-card">
                    <div class="report-header">
                        <div>
                            <div class="report-title">{{ $report->title }}</div>
                            <div class="report-id">{{ $report->ticket_number }}</div>
                        </div>
                        <div class="status-badge status-{{ $report->status_color }}">{{ ucfirst(str_replace('-', ' ', $report->status)) }}</div>
                    </div>
                    <div class="report-meta">
                        <div class="report-meta-item">
                            <span>📍</span> {{ $report->equipment->lab->name }}, {{ $report->equipment->equipment_code }}
                        </div>
                        <div class="report-meta-item">
                            <span>🕒</span> {{ $report->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="report-description">
                        {{ Str::limit($report->description, 100) }}
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No reports yet</h3>
                    <p>Get started by reporting your first issue</p>
                    <a href="{{ route('user.reports.create') }}" class="btn btn-primary" style="margin-top: 1rem;">+ Report Issue</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection