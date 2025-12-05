@extends('layouts.app')

@section('title', 'IT Ticket Management')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div id="it-ticket-detail" class="container">
        <a href="{{ route('it.tickets.index') }}" class="back-btn">← Back to Queue</a>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="header-top">
                <div>
                    <h1 class="ticket-title">{{ $ticket->title }}</h1>
                    <div class="ticket-id">Ticket {{ $ticket->ticket_number }}</div>
                </div>
                <div class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }} Priority</div>
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
                    <div class="meta-value">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Submitted</div>
                    <div class="meta-value">{{ $ticket->created_at->format('M d, Y - g:i A') }}</div>
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

                <!-- Attachments -->
                @if($ticket->attachments && count($ticket->attachments) > 0)
                    <div class="card">
                        <h2 class="card-title">Attachments</h2>
                        <div class="attachments-grid">
                            @foreach($ticket->attachments as $attachment)
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

                <!-- Internal Notes (IT Only) -->
                <div class="card">
                    <h2 class="card-title">Internal Notes (IT Team Only)</h2>
                    <div class="notes-list">
                        <!-- TODO: Add notes system in next phase -->
                        <p style="color: #9ca3af; text-align: center; padding: 2rem;">
                            Internal notes feature coming soon
                        </p>
                    </div>
                </div>

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
                                        <span class="timeline-time">{{ $ticket->resolved_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="timeline-text">
                                        Marked as resolved by {{ $ticket->assignedTo ? $ticket->assignedTo->full_name : 'IT Support' }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($ticket->assigned_at)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">Ticket Assigned</span>
                                        <span class="timeline-time">{{ $ticket->assigned_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="timeline-text">
                                        Assigned to {{ $ticket->assignedTo ? $ticket->assignedTo->full_name : 'IT Support' }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Created</span>
                                    <span class="timeline-time">{{ $ticket->created_at->diffForHumans() }}</span>
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
                <!-- Ticket Management -->
                <div class="card">
                    <h2 class="card-title">Ticket Management</h2>
                    
                    <form action="{{ route('it.tickets.update', $ticket->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="control-group">
                            <label class="control-label">Status</label>
                            <select name="status" required>
                                <option value="new" {{ $ticket->status === 'new' ? 'selected' : '' }}>New</option>
                                <option value="assigned" {{ $ticket->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="in-progress" {{ $ticket->status === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <div class="control-group">
                            <label class="control-label">Priority</label>
                            <select name="priority" required>
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                            </select>
                        </div>

                        <div class="control-group">
                            <label class="control-label">Assign To</label>
                            <select name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($itStaff as $staff)
                                    <option value="{{ $staff->id }}" {{ $ticket->assigned_to == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Update & Notify User</button>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Reporter Info -->
                <div class="card">
                    <h2 class="card-title">Reporter Information</h2>
                    <div class="reporter-info">
                        <div class="reporter-avatar">{{ $ticket->reporter->initials }}</div>
                        <div class="reporter-name">{{ $ticket->reporter->full_name }}</div>
                        <div class="reporter-role">{{ ucfirst(str_replace('-', ' ', $ticket->reporter->role)) }}</div>
                    </div>
                    <button class="btn btn-secondary" style="margin-top: 1rem;">Send Message</button>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h2 class="card-title">Quick Actions</h2>
                    <div class="action-buttons">
                        @if($ticket->status !== 'resolved')
                            <form action="{{ route('it.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-primary">Mark as Resolved</button>
                            </form>
                        @endif
                        
                        @if(!$ticket->assigned_to || $ticket->assigned_to !== auth()->id())
                            <form action="{{ route('it.tickets.assign-self', $ticket->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Assign to Me</button>
                            </form>
                        @endif
                        
                        <button class="btn btn-secondary">Request More Info</button>
                        <button class="btn btn-danger">Escalate Issue</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection