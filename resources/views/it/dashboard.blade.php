@extends('layouts.app')

@section('title', 'IT Support Dashboard')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div id="it-dashboard" class="container">
        <div class="page-header">
            <h1>IT Support Dashboard</h1>
            <p>Welcome back, {{ auth()->user()->first_name ?? 'IT Support' }}! Here's your ticket overview</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Open Tickets</span>
                    <span class="stat-icon">📋</span>
                </div>
                <div class="stat-value">45</div>
                <div class="stat-change">+12 from yesterday</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">My Assignments</span>
                    <span class="stat-icon">👤</span>
                </div>
                <div class="stat-value">8</div>
                <div class="stat-change">3 new today</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">High Priority</span>
                    <span class="stat-icon">🔴</span>
                </div>
                <div class="stat-value">12</div>
                <div class="stat-change negative">+4 urgent</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Resolved Today</span>
                    <span class="stat-icon">✅</span>
                </div>
                <div class="stat-value">23</div>
                <div class="stat-change">Avg. 2.3 hrs</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Avg Response Time</span>
                    <span class="stat-icon">⏱️</span>
                </div>
                <div class="stat-value">1.2h</div>
                <div class="stat-change">-15 min improvement</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Team Performance</span>
                    <span class="stat-icon">📊</span>
                </div>
                <div class="stat-value">94%</div>
                <div class="stat-change">Satisfaction rate</div>
            </div>
        </div>

        <!-- Priority Alerts -->
        <div class="alerts-section">
            <div class="alerts-header">
                <div class="alerts-title">
                    ⚠️ High Priority Alerts
                    <span class="alert-badge">3</span>
                </div>
            </div>
            <div class="alerts-grid">
                <div class="alert-item">
                    <div class="alert-info">
                        <h4>#087 - Computer Lab A Server Down</h4>
                        <p>Reported 15 minutes ago • Affects 20 workstations</p>
                    </div>
                    <button class="alert-action">Take Action</button>
                </div>
                <div class="alert-item">
                    <div class="alert-info">
                        <h4>#089 - Network Outage in Lab C</h4>
                        <p>Reported 30 minutes ago • Critical</p>
                    </div>
                    <button class="alert-action">Take Action</button>
                </div>
                <div class="alert-item">
                    <div class="alert-info">
                        <h4>#091 - Multiple computers won't boot</h4>
                        <p>Reported 1 hour ago • 5 affected units</p>
                    </div>
                    <button class="alert-action">Take Action</button>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Tickets -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Recent Tickets</h2>
                    <a href="{{ route('it.tickets.index') }}" class="view-all-link">View All →</a>
                </div>
                <div class="ticket-list">
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-id">#092</span>
                            <span class="status-badge status-new">New</span>
                        </div>
                        <div class="ticket-title">Mouse not responding on PC-15</div>
                        <div class="ticket-meta">
                            <span>Lab B</span>
                            <span>5 minutes ago</span>
                            <span class="priority-high">High Priority</span>
                        </div>
                    </div>

                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-id">#091</span>
                            <span class="status-badge status-new">New</span>
                        </div>
                        <div class="ticket-title">Software installation fails repeatedly</div>
                        <div class="ticket-meta">
                            <span>Lab C</span>
                            <span>12 minutes ago</span>
                            <span>Medium Priority</span>
                        </div>
                    </div>

                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-id">#090</span>
                            <span class="status-badge status-new">New</span>
                        </div>
                        <div class="ticket-title">Monitor display flickering</div>
                        <div class="ticket-meta">
                            <span>Lab A</span>
                            <span>25 minutes ago</span>
                            <span>Low Priority</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- Quick Actions -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Quick Actions</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="{{ route('it.tickets.index') }}" class="activity-item">
                            <span class="activity-icon">📥</span>
                            <div class="activity-time">
                                <h4 class="activity-title">View Unassigned</h4>
                                <p class="activity-desc">18 tickets waiting</p>
                            </div>
                        </a>
                        <button class="activity-item">
                            <span class="activity-icon">📊</span>
                            <div class="activity-time">
                                <h4 class="activity-title">Generate Report</h4>
                                <p class="activity-desc">Weekly summary</p>
                            </div>
                        </button>
                        <a href="{{ route('it.knowledge-base') }}" class="activity-item">
                            <span class="activity-icon">📚</span>
                            <div class="activity-time">
                                <h4 class="activity-title">Knowledge Base</h4>
                                <p class="activity-desc">Manage articles</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Recent Activity</h2>
                    </div>
                    <div class="activity-feed">
                        <div class="activity-item">
                            <div class="activity-icon">✅</div>
                            <div class="activity-content">
                                <div class="activity-title">Ticket #085 resolved</div>
                                <div class="activity-time">5 minutes ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">👤</div>
                            <div class="activity-content">
                                <div class="activity-title">You were assigned #088</div>
                                <div class="activity-time">15 minutes ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">💬</div>
                            <div class="activity-content">
                                <div class="activity-title">New comment on #082</div>
                                <div class="activity-time">1 hour ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection