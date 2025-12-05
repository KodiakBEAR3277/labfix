@extends('layouts.app')

@section('title', 'Report Detail')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div id="user-report-details" class="container">
        <a href="{{ route('user.reports.index') }}" class="back-btn">← Back to My Reports</a>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="ticket-title-row">
                <div>
                    <h1 class="ticket-title">{{ $report->title }}</h1>
                    <div class="ticket-id">Ticket {{ $report->ticket_number }}</div>
                </div>
                <span class="status-badge status-{{ $report->status_color }}">
                    {{ ucfirst(str_replace('-', ' ', $report->status)) }}
                </span>
            </div>

            <div class="ticket-meta">
                <div class="meta-item">
                    <div class="meta-label">Reported By</div>
                    <div class="meta-value">{{ $report->reporter->full_name }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Lab Location</div>
                    <div class="meta-value">{{ $report->equipment->lab->name }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Equipment</div>
                    <div class="meta-value">{{ $report->equipment->equipment_code }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <div class="meta-value">{{ ucfirst($report->category) }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Priority</div>
                    <div class="meta-value priority-{{ $report->priority }}">{{ ucfirst($report->priority) }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Submitted</div>
                    <div class="meta-value">{{ $report->created_at->format('M d, Y - g:i A') }}</div>
                </div>
                @if($report->assignedTo)
                    <div class="meta-item">
                        <div class="meta-label">Assigned To</div>
                        <div class="meta-value">{{ $report->assignedTo->full_name }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="detail-layout">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Description -->
                <div class="content-card">
                    <h2 class="card-title">Problem Description</h2>
                    <p class="description-text">{{ $report->description }}</p>
                </div>

                <!-- Equipment Details -->
                <div class="content-card">
                    <h2 class="card-title">Equipment Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-label">Equipment Code</div>
                            <div class="info-value">{{ $report->equipment->equipment_code }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Equipment Type</div>
                            <div class="info-value">{{ ucfirst($report->equipment->type) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Status</div>
                            <div class="info-value">
                                <span class="status-badge status-{{ $report->equipment->status === 'operational' ? 'active' : ($report->equipment->status === 'has-issue' ? 'new' : 'warning') }}">
                                    {{ ucfirst(str_replace('-', ' ', $report->equipment->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Lab Location</div>
                            <div class="info-value">{{ $report->equipment->lab->name }}</div>
                        </div>
                        @if($report->equipment->lab->location)
                            <div class="info-item">
                                <div class="info-label">Building/Floor</div>
                                <div class="info-value">{{ $report->equipment->lab->location }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attachments -->
                @if($report->attachments && count($report->attachments) > 0)
                    <div class="content-card">
                        <h2 class="card-title">Attachments</h2>
                        <div class="attachments-grid">
                            @foreach($report->attachments as $attachment)
                                <a href="{{ Storage::url($attachment) }}" target="_blank" class="attachment-item">
                                    <div class="attachment-icon">
                                        @if(str_ends_with($attachment, '.pdf'))
                                            📄
                                        @else
                                            🖼️
                                        @endif
                                    </div>
                                    <div class="attachment-name">{{ basename($attachment) }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Activity Timeline -->
                <div class="content-card">
                    <h2 class="card-title">Activity Timeline</h2>
                    <div class="timeline">
                        @if($report->closed_at)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Ticket Closed</span>
                                        <span class="timeline-time">{{ $report->closed_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <p class="timeline-text">Issue marked as closed</p>
                                </div>
                            </div>
                        @endif

                        @if($report->resolved_at)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Ticket Resolved</span>
                                        <span class="timeline-time">{{ $report->resolved_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <p class="timeline-text">Issue marked as resolved</p>
                                </div>
                            </div>
                        @endif

                        @if($report->assigned_at)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Ticket Assigned</span>
                                        <span class="timeline-time">{{ $report->assigned_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <p class="timeline-text">
                                        Assigned to {{ $report->assignedTo->full_name ?? 'IT Support' }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Created</span>
                                    <span class="timeline-time">{{ $report->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <p class="timeline-text">
                                    Report submitted by {{ $report->reporter->full_name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Info -->
                <div class="info-card">
                    <h2 class="card-title">Quick Information</h2>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">{{ ucfirst(str_replace('-', ' ', $report->status)) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority</span>
                        <span class="info-value priority-{{ $report->priority }}">{{ ucfirst($report->priority) }}</span>
                    </div>
                    @if($report->assigned_at)
                        <div class="info-row">
                            <span class="info-label">Response Time</span>
                            <span class="info-value">{{ $report->created_at->diffForHumans($report->assigned_at, true) }}</span>
                        </div>
                    @endif
                    @if($report->resolved_at && $report->created_at)
                        <div class="info-row">
                            <span class="info-label">Resolution Time</span>
                            <span class="info-value">{{ $report->created_at->diffForHumans($report->resolved_at, true) }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Age</span>
                        <span class="info-value">{{ $report->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                <!-- Assigned Technician -->
                @if($report->assignedTo)
                    <div class="info-card">
                        <h2 class="card-title">Assigned Technician</h2>
                        <div style="text-align: center; padding: 1rem 0;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #2dd4bf, #14b8a6); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold;">
                                {{ $report->assignedTo->initials }}
                            </div>
                            <div style="color: #ffffff; font-weight: 600; margin-bottom: 0.25rem;">
                                {{ $report->assignedTo->full_name }}
                            </div>
                            <div style="color: #9ca3af; font-size: 0.9rem;">
                                {{ ucfirst(str_replace('-', ' ', $report->assignedTo->role)) }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="info-card">
                        <h2 class="card-title">Status</h2>
                        <div style="text-align: center; padding: 1rem 0; color: #9ca3af;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                            <p>Awaiting assignment to a technician</p>
                        </div>
                    </div>
                @endif

                <!-- Lab Status -->
                <div class="info-card">
                    <h2 class="card-title">Lab Status</h2>
                    <div class="info-row">
                        <span class="info-label">Lab</span>
                        <span class="info-value">{{ $report->equipment->lab->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Operational Equipment</span>
                        <span class="info-value">{{ $report->equipment->lab->operational_count }}/{{ $report->equipment->lab->capacity }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lab Status</span>
                        <span class="info-value">
                            <span class="status-badge {{ $report->equipment->lab->isOperational() ? 'status-active' : 'status-warning' }}">
                                {{ $report->equipment->lab->isOperational() ? 'Operational' : 'Limited' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection