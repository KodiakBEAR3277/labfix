@extends('layouts.app')

@section('title', 'IT Support Dashboard')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div id="it-dashboard" class="container">
        <div class="page-header">
            <h1>IT Support Dashboard</h1>
            <p>Welcome back, {{ auth()->user()->first_name }}! Here's your ticket overview</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Open Tickets</span>
                    <span class="stat-icon">📋</span>
                </div>
                <div class="stat-value">{{ $stats['open_tickets'] }}</div>
                <div class="stat-change">{{ $stats['open_tickets'] > 20 ? 'High volume' : 'Normal' }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">My Assignments</span>
                    <span class="stat-icon">👤</span>
                </div>
                <div class="stat-value">{{ $stats['my_assignments'] }}</div>
                <div class="stat-change">Active tasks</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">High Priority</span>
                    <span class="stat-icon">🔴</span>
                </div>
                <div class="stat-value">{{ $stats['high_priority'] }}</div>
                <div class="stat-change {{ $stats['high_priority'] > 5 ? 'negative' : '' }}">{{ $stats['high_priority'] > 5 ? 'Needs attention' : 'Under control' }}</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Resolved Today</span>
                    <span class="stat-icon">✅</span>
                </div>
                <div class="stat-value">{{ $stats['resolved_today'] }}</div>
                <div class="stat-change">Great progress!</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Avg Response Time</span>
                    <span class="stat-icon">⏱️</span>
                </div>
                <div class="stat-value">{{ $stats['avg_response_time'] }}h</div>
                <div class="stat-change">Last 7 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Team Performance</span>
                    <span class="stat-icon">📊</span>
                </div>
                <div class="stat-value">{{ $stats['team_satisfaction'] }}%</div>
                <div class="stat-change">Resolution rate</div>
            </div>
        </div>

        <!-- Priority Alerts -->
        @if($priorityAlerts->count() > 0)
            <div class="alerts-section">
                <div class="alerts-header">
                    <div class="alerts-title">
                        ⚠️ High Priority Alerts
                        <span class="alert-badge">{{ $priorityAlerts->count() }}</span>
                    </div>
                </div>
                <div class="alerts-grid">
                    @foreach($priorityAlerts as $alert)
                        <div class="alert-item">
                            <div class="alert-info">
                                <h4>{{ $alert->ticket_number }} - {{ $alert->title }}</h4>
                                <p>Reported {{ $alert->created_at->diffForHumans() }} • {{ $alert->equipment->lab->name }}</p>
                            </div>
                            <a href="{{ route('it.tickets.show', $alert->id) }}" class="alert-action">Take Action</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Tickets -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Recent Tickets</h2>
                    <a href="{{ route('it.tickets.index') }}" class="view-all-link">View All →</a>
                </div>
                <div class="ticket-list">
                    @forelse($recentTickets->take(5) as $ticket)
                        <div class="ticket-item">
                            <div class="ticket-header">
                                <span class="ticket-id">{{ $ticket->ticket_number }}</span>
                                <span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                            </div>
                            <div class="ticket-title">{{ $ticket->title }}</div>
                            <div class="ticket-meta">
                                <span>{{ $ticket->equipment->lab->name }}</span>
                                <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                <span class="priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }} Priority</span>
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: #9ca3af; padding: 2rem;">No recent tickets</p>
                    @endforelse
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
                        <a href="{{ route('it.tickets.index', ['status' => 'new']) }}" class="activity-item">
                            <span class="activity-icon">🔥</span>
                            <div class="activity-time">
                                <h4 class="activity-title">View Unassigned</h4>
                                <p class="activity-desc">{{ \App\Models\Report::where('status', 'new')->count() }} tickets waiting</p>
                            </div>
                        </a>
                        <a href="{{ route('it.assignments.index') }}" class="activity-item">
                            <span class="activity-icon">👤</span>
                            <div class="activity-time">
                                <h4 class="activity-title">My Assignments</h4>
                                <p class="activity-desc">{{ $stats['my_assignments'] }} active</p>
                            </div>
                        </a>
                        <a href="{{ route('it.knowledge-base.index') }}" class="activity-item">
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
                        @php
                            $recentResolved = \App\Models\Report::whereNotNull('resolved_at')
                                ->latest('resolved_at')
                                ->take(3)
                                ->get();
                        @endphp
                        @forelse($recentResolved as $resolved)
                            <div class="activity-item">
                                <div class="activity-icon">✅</div>
                                <div class="activity-content">
                                    <div class="activity-title">Ticket {{ $resolved->ticket_number }} resolved</div>
                                    <div class="activity-time">{{ $resolved->resolved_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @empty
                            <p style="text-align: center; color: #9ca3af; padding: 1rem;">No recent activity</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection