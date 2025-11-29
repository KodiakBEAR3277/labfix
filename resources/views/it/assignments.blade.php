@extends('layouts.app')

@section('title', 'My Assignments')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>My Assignments</h1>
            <p>Tickets currently assigned to you</p>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Assigned</div>
                <div class="stat-value">8</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">5</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">High Priority</div>
                <div class="stat-value">3</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">4</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="tab active">All Assignments (8)</button>
            <button class="tab">In Progress (5)</button>
            <button class="tab">High Priority (3)</button>
            <button class="tab">Due Today (2)</button>
        </div>

        <!-- Tickets Grid -->
        <div class="tickets-grid">
            <div class="ticket-card priority-high">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#089</div>
                        <h3 class="ticket-title">Network connectivity issue in Lab C</h3>
                    </div>
                    <span class="priority-badge priority-high">🔴 High</span>
                </div>
                <div class="ticket-meta">
                    <div class="meta-item">
                        <span>👤</span> Alice Brown
                    </div>
                    <div class="meta-item">
                        <span>📍</span> Lab C, All PCs
                    </div>
                    <div class="meta-item">
                        <span>🕒</span> 1 hour ago
                    </div>
                    <div class="meta-item">
                        <span>🏷️</span> Network
                    </div>
                </div>
                <p class="ticket-description">
                    All computers in Lab C unable to connect to internet. Network switch appears to be non-responsive. Students unable to complete online assignments.
                </p>
                <div class="ticket-footer">
                    <span class="status-badge status-progress">In Progress</span>
                    <div class="ticket-actions">
                        <button class="action-btn">Update Status</button>
                        <a href="{{ route('it.tickets.show', 89) }}" class="action-btn">View Details</a>
                    </div>
                </div>
            </div>

            <div class="ticket-card priority-high">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#087</div>
                        <h3 class="ticket-title">Computer won't boot - black screen</h3>
                    </div>
                    <span class="priority-badge priority-high">🔴 High</span>
                </div>
                <div class="ticket-meta">
                    <div class="meta-item">
                        <span>👤</span> Emma Davis
                    </div>
                    <div class="meta-item">
                        <span>📍</span> Lab B, PC-05
                    </div>
                    <div class="meta-item">
                        <span>🕒</span> 3 hours ago
                    </div>
                    <div class="meta-item">
                        <span>🏷️</span> Hardware
                    </div>
                </div>
                <p class="ticket-description">
                    Computer shows black screen on startup. No POST beep detected. Power LED is on but no display output.
                </p>
                <div class="ticket-footer">
                    <span class="status-badge status-progress">In Progress</span>
                    <div class="ticket-actions">
                        <button class="action-btn">Update Status</button>
                        <button class="action-btn">View Details</button>
                    </div>
                </div>
            </div>

            <div class="ticket-card">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#091</div>
                        <h3 class="ticket-title">Software installation repeatedly fails</h3>
                    </div>
                    <span class="priority-badge priority-medium">🟡 Medium</span>
                </div>
                <div class="ticket-meta">
                    <div class="meta-item">
                        <span>👤</span> Jane Smith
                    </div>
                    <div class="meta-item">
                        <span>📍</span> Lab C, PC-20
                    </div>
                    <div class="meta-item">
                        <span>🕒</span> 12 min ago
                    </div>
                    <div class="meta-item">
                        <span>🏷️</span> Software
                    </div>
                </div>
                <p class="ticket-description">
                    Visual Studio Code installation keeps failing with error message. Tried multiple times with same result.
                </p>
                <div class="ticket-footer">
                    <span class="status-badge status-assigned">Assigned</span>
                    <div class="ticket-actions">
                        <button class="action-btn">Start Working</button>
                        <button class="action-btn">View Details</button>
                    </div>
                </div>
            </div>

            <div class="ticket-card priority-high">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#094</div>
                        <h3 class="ticket-title">Multiple monitors not displaying</h3>
                    </div>
                    <span class="priority-badge priority-high">🔴 High</span>
                </div>
                <div class="ticket-meta">
                    <div class="meta-item">
                        <span>👤</span> Prof. Martinez
                    </div>
                    <div class="meta-item">
                        <span>📍</span> Multimedia Lab
                    </div>
                    <div class="meta-item">
                        <span>🕒</span> 45 min ago
                    </div>
                    <div class="meta-item">
                        <span>🏷️</span> Hardware
                    </div>
                </div>
                <p class="ticket-description">
                    Five workstations in multimedia lab showing no display. Class scheduled in 2 hours. Urgent assistance needed.
                </p>
                <div class="ticket-footer">
                    <span class="status-badge status-progress">In Progress</span>
                    <div class="ticket-actions">
                        <button class="action-btn">Update Status</button>
                        <button class="action-btn">View Details</button>
                    </div>
                </div>
            </div>

            <div class="ticket-card">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#093</div>
                        <h3 class="ticket-title">Printer not responding to print jobs</h3>
                    </div>
                    <span class="priority-badge priority-medium">🟡 Medium</span>
                </div>
                <div class="ticket-meta">
                    <div class="meta-item">
                        <span>👤</span> Staff Office
                    </div>
                    <div class="meta-item">
                        <span>📍</span> Lab A, Printer
                    </div>
                    <div class="meta-item">
                        <span>🕒</span> 2 hours ago
                    </div>
                    <div class="meta-item">
                        <span>🏷️</span> Hardware
                    </div>
                </div>
                <p class="ticket-description">
                    Lab A printer queue shows jobs pending but nothing prints. Printer online and has paper/toner.
                </p>
                <div class="ticket-footer">
                    <span class="status-badge status-progress">In Progress</span>
                    <div class="ticket-actions">
                        <button class="action-btn">Update Status</button>
                        <button class="action-btn">View Details</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection