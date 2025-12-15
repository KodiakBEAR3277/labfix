@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>System Administration</h1>
            <p>Manage users, system configuration, and monitor overall performance</p>
        </div>

        <!-- System Overview Stats -->
        <div class="system-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-icon">👥</span>
                </div>
                <div class="stat-value">{{ $stats['total_users'] }}</div>
                <div class="stat-detail">{{ $stats['students'] }} Students, {{ $stats['staff'] }} Staff, {{ $stats['it_support'] }} IT Support</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Open Tickets</span>
                    <span class="stat-icon">📋</span>
                </div>
                <div class="stat-value">{{ $stats['open_tickets'] }}</div>
                <div class="stat-detail">{{ $stats['high_priority'] }} High Priority</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">System Uptime</span>
                    <span class="stat-icon">⚡</span>
                </div>
                <div class="stat-value">{{ $stats['system_uptime'] }}%</div>
                <div class="stat-detail">Last 30 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Labs</span>
                    <span class="stat-icon">💻</span>
                </div>
                <div class="stat-value">{{ $stats['total_labs'] }}</div>
                <div class="stat-detail">{{ $stats['total_workstations'] }} total workstations</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="{{ route('admin.users.index') }}" class="action-card">
                <div class="action-icon">👤</div>
                <h3 class="action-title">User Management</h3>
                <p class="action-description">Add, edit, or remove users and manage roles</p>
            </a>

            <a href="{{ route('admin.tickets.index') }}" class="action-card">
                <div class="action-icon">📋</div>
                <h3 class="action-title">Ticket Management</h3>
                <p class="action-description">Add, edit, or remove users and manage roles</p>
            </a>

            <a href="{{ route('admin.settings') }}" class="action-card">
                <div class="action-icon">⚙️</div>
                <h3 class="action-title">System Settings</h3>
                <p class="action-description">Configure system-wide settings and preferences</p>
            </a>

            <a href="{{ route('admin.labs.index') }}" class="action-card">
                <div class="action-icon">🖥️</div>
                <h3 class="action-title">Lab Configuration</h3>
                <p class="action-description">Manage lab layouts and equipment IDs</p>
            </a>

            <a href="{{ route('it.tickets.index') }}" class="action-card">
                <div class="action-icon">📊</div>
                <h3 class="action-title">View Reports</h3>
                <p class="action-description">Monitor tickets and system usage</p>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Recent System Activity</h2>
                    <a href="{{ route('admin.users.index') }}" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                    @forelse($recentActivity as $activity)
                        <div class="activity-item">
                            <div class="activity-icon">{{ $activity['icon'] }}</div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $activity['title'] }}</div>
                                <div class="activity-time">{{ $activity['time'] }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: #9ca3af; padding: 2rem;">No recent activity</p>
                    @endforelse
                </div>
            </div>

            <!-- System Health -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">System Health</h2>
                </div>
                <div class="health-items">
                    @foreach($systemHealth as $key => $health)
                        <div class="health-item">
                            <div class="health-header">
                                <span class="health-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                <span class="health-status status-{{ $health['color'] }}">{{ $health['status'] }}</span>
                            </div>
                            <div class="health-bar">
                                <div class="health-bar-fill" style="width: {{ $health['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection