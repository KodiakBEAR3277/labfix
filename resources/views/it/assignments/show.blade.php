@extends('layouts.app')

@section('title', 'My Assignment')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div id="it-ticket-detail" class="container">
        <a href="{{ route('it.assignments.index') }}" class="back-btn">← Back to My Assignments</a>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="header-top">
                <div>
                    <h1 class="ticket-title">{{ $ticket->title }}</h1>
                    <div class="ticket-id">Ticket {{ $ticket->ticket_number }} • Assigned to Me</div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }} Priority</span>
                    <a href="{{ route('it.assignments.edit', $ticket->id) }}" class="btn btn-primary">Update Status</a>
                </div>
            </div>

            <div class="ticket-meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Reporter</div>
                    <div class="meta-value">{{ $ticket->reporter->full_name }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Location</div>
                    <div class="meta-value">{{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <div class="meta-value">{{ ucfirst($ticket->category) }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Assigned</div>
                    <div class="meta-value">{{ $ticket->assigned_at ? $ticket->assigned_at->diffForHumans() : 'Just now' }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Last Updated</div>
                    <div class="meta-value">{{ $ticket->updated_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>

        <div class="content-layout">
            <!-- Main Content -->
            <div class="content-section">
                <!-- Description -->
                <div class="card">
                    <h2 class="card-title">Problem Description</h2>
                    <p class="description-text">
                        {{ $ticket->description }}
                    </p>
                </div>

                <!-- Equipment Details -->
                <div class="card">
                    <h2 class="card-title">Equipment Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-label">Equipment Code</div>
                            <div class="info-value">{{ $ticket->equipment->equipment_code }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Equipment Type</div>
                            <div class="info-value">{{ ucfirst($ticket->equipment->type) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Status</div>
                            <div class="info-value">
                                <span class="status-badge status-{{ $ticket->equipment->status === 'operational' ? 'active' : ($ticket->equipment->status === 'has-issue' ? 'new' : 'warning') }}">
                                    {{ ucfirst(str_replace('-', ' ', $ticket->equipment->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Lab Location</div>
                            <div class="info-value">{{ $ticket->equipment->lab->name }}</div>
                        </div>
                        @if($ticket->equipment->lab->location)
                            <div class="info-item">
                                <div class="info-label">Building/Floor</div>
                                <div class="info-value">{{ $ticket->equipment->lab->location }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attachments -->
                @if($ticket->attachments && count($ticket->attachments) > 0)
                    <div class="card">
                        <h2 class="card-title">Attachments</h2>
                        <div class="attachments-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                            @foreach($ticket->attachments as $attachment)
                                <a href="{{ Storage::url($attachment) }}" target="_blank" class="attachment-item" style="display: flex; flex-direction: column; align-items: center; padding: 1rem; background: rgba(45, 212, 191, 0.1); border-radius: 8px; text-decoration: none;">
                                    <div class="attachment-icon" style="font-size: 2rem; margin-bottom: 0.5rem;">
                                        @if(str_ends_with($attachment, '.pdf'))
                                            📄
                                        @else
                                            🖼️
                                        @endif
                                    </div>
                                    <div class="attachment-name" style="font-size: 0.85rem; color: #2dd4bf; text-align: center;">{{ basename($attachment) }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Activity Timeline -->
                <div class="card">
                    <h2 class="card-title">Activity Timeline</h2>
                    <div class="timeline">
                        @if($ticket->resolved_at)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Ticket Resolved</span>
                                        <span class="timeline-time">{{ $ticket->resolved_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <p class="timeline-text">You marked this ticket as resolved</p>
                                </div>
                            </div>
                        @endif

                        @if($ticket->status === 'in-progress')
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Work Started</span>
                                        <span class="timeline-time">{{ $ticket->updated_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                    <p class="timeline-text">You started working on this ticket</p>
                                </div>
                            </div>
                        @endif

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Assigned to Me</span>
                                    <span class="timeline-time">{{ $ticket->assigned_at ? $ticket->assigned_at->format('M d, Y g:i A') : $ticket->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <p class="timeline-text">This ticket was assigned to you</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Created</span>
                                    <span class="timeline-time">{{ $ticket->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <p class="timeline-text">
                                    Report submitted by {{ $ticket->reporter->full_name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Actions -->
                <div class="card">
                    <h2 class="card-title">Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="{{ route('it.assignments.edit', $ticket->id) }}" class="btn btn-primary">Update Status</a>
                        
                        @if($ticket->status !== 'in-progress' && $ticket->status !== 'resolved')
                            <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in-progress">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <button type="submit" class="btn btn-secondary">Start Working</button>
                            </form>
                        @endif

                        @if($ticket->status !== 'resolved')
                            <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <button type="submit" class="btn btn-secondary">Mark as Resolved</button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- My Progress -->
                <div class="card">
                    <h2 class="card-title">My Progress</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Current Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority</span>
                            <span class="info-value priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Time on Task</span>
                            <span class="info-value">{{ $ticket->assigned_at ? $ticket->assigned_at->diffForHumans(null, true) : 'Just assigned' }}</span>
                        </div>
                        @if($ticket->resolved_at)
                            <div class="info-item">
                                <span class="info-label">Resolution Time</span>
                                <span class="info-value">{{ $ticket->assigned_at->diffForHumans($ticket->resolved_at, true) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Reporter Info -->
                <div class="card">
                    <h2 class="card-title">Reporter Information</h2>
                    <div class="reporter-info">
                        <div class="reporter-avatar">{{ $ticket->reporter->initials }}</div>
                        <div class="reporter-name">{{ $ticket->reporter->full_name }}</div>
                        <div class="reporter-role">{{ ucfirst(str_replace('-', ' ', $ticket->reporter->role)) }}</div>
                        @if($ticket->reporter->email)
                            <p style="color: #9ca3af; margin-top: 0.5rem; font-size: 0.9rem;">{{ $ticket->reporter->email }}</p>
                        @endif
                    </div>
                </div>

                <!-- Next Steps -->
                @if($ticket->status !== 'resolved')
                    <div class="card" style="background: rgba(45, 212, 191, 0.1); border-color: rgba(45, 212, 191, 0.3);">
                        <h2 class="card-title" style="color: #2dd4bf;">💡 Next Steps</h2>
                        @if($ticket->status === 'assigned')
                            <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                                Start working on this ticket to let the reporter know you're actively resolving their issue.
                            </p>
                        @elseif($ticket->status === 'in-progress')
                            <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                                Once you've resolved the issue, mark this ticket as resolved to notify the reporter.
                            </p>
                        @endif
                    </div>
                @else
                    <div class="card" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                        <h2 class="card-title" style="color: #10b981;">✅ Great Job!</h2>
                        <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                            You've successfully resolved this ticket. The reporter has been notified.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection