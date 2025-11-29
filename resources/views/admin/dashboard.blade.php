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
                <div class="stat-value">150</div>
                <div class="stat-detail">45 Students, 15 Staff, 10 IT Support</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Open Tickets</span>
                    <span class="stat-icon">📋</span>
                </div>
                <div class="stat-value">45</div>
                <div class="stat-detail">12 High Priority, 23 Medium, 10 Low</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">System Uptime</span>
                    <span class="stat-icon">⚡</span>
                </div>
                <div class="stat-value">99.8%</div>
                <div class="stat-detail">Last 30 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Labs</span>
                    <span class="stat-icon">💻</span>
                </div>
                <div class="stat-value">6</div>
                <div class="stat-detail">120 total workstations</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="{{ route('admin.users.index') }}" class="action-card">
                <div class="action-icon">👤</div>
                <h3 class="action-title">User Management</h3>
                <p class="action-description">Add, edit, or remove users and manage roles</p>
            </a>

            <a href="{{ route('admin.settings') }}" class="action-card">
                <div class="action-icon">⚙️</div>
                <h3 class="action-title">System Settings</h3>
                <p class="action-description">Configure system-wide settings and preferences</p>
            </a>

            <a href="{{ route('admin.labs') }}" class="action-card">
                <div class="action-icon">🖥️</div>
                <h3 class="action-title">Lab Configuration</h3>
                <p class="action-description">Manage lab layouts and equipment IDs</p>
            </a>

            <div class="action-card">
                <div class="action-icon">📊</div>
                <h3 class="action-title">Generate Reports</h3>
                <p class="action-description">View analytics on tickets and system usage</p>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Recent System Activity</h2>
                    <a href="#" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">👤</div>
                        <div class="activity-content">
                            <div class="activity-title">New user registered: John Smith (Student)</div>
                            <div class="activity-time">5 minutes ago</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon">⚙️</div>
                        <div class="activity-content">
                            <div class="activity-title">System settings updated: Notification preferences</div>
                            <div class="activity-time">15 minutes ago</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon">🖥️</div>
                        <div class="activity-content">
                            <div class="activity-title">Lab Configuration modified: Computer Lab C</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <div class="activity-title">User role changed: Sarah Lee promoted to IT Support</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon">📋</div>
                        <div class="activity-content">
                            <div class="activity-title">System backup completed successfully</div>
                            <div class="activity-time">3 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">System Health</h2>
                </div>
                <div class="health-items">
                    <div class="health-item">
                        <div class="health-header">
                            <span class="health-label">Database</span>
                            <span class="health-status status-good">Operational</span>
                        </div>
                        <div class="health-bar">
                            <div class="health-bar-fill" style="width: 95%"></div>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-header">
                            <span class="health-label">Server Load</span>
                            <span class="health-status status-good">Normal</span>
                        </div>
                        <div class="health-bar">
                            <div class="health-bar-fill" style="width: 45%"></div>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-header">
                            <span class="health-label">Storage</span>
                            <span class="health-status status-warning">75% Used</span>
                        </div>
                        <div class="health-bar">
                            <div class="health-bar-fill" style="width: 75%"></div>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-header">
                            <span class="health-label">Email Service</span>
                            <span class="health-status status-good">Active</span>
                        </div>
                        <div class="health-bar">
                            <div class="health-bar-fill" style="width: 98%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection