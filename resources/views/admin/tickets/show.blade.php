@extends('layouts.app')

@section('title', 'View Ticket')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div id="it-ticket-detail" class="container">
        <a href="{{ route('admin.tickets.index') }}" class="back-btn">← Back to Ticket Management</a>

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
                    <div class="ticket-id">Ticket {{ $ticket->ticket_number }}</div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }} Priority</span>
                    <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-primary">Edit Ticket</a>
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
                <x-ticket-timeline :ticket="$ticket" />
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Info -->
                <div class="card">
                    <h2 class="card-title">Quick Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority</span>
                            <span class="info-value priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                        @php
                            $assignedTransaction = $ticket->transactions()->where('action', 'assigned')->first();
                            $resolvedTransaction = $ticket->transactions()->where('action', 'status_changed')->where('new_value', 'resolved')->first();
                        @endphp

                        @if($assignedTransaction)
                            <div class="info-item">
                                <span class="info-label">Response Time</span>
                                <span class="info-value">{{ $ticket->created_at->diffForHumans($assignedTransaction->created_at, true) }}</span>
                            </div>
                        @endif

                        @if($resolvedTransaction)
                            <div class="info-item">
                                <span class="info-label">Resolution Time</span>
                                <span class="info-value">{{ $ticket->created_at->diffForHumans($resolvedTransaction->created_at, true) }}</span>
                            </div>
                        @endif
                        <div class="info-item">
                            <span class="info-label">Age</span>
                            <span class="info-value">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
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
                        @if($ticket->reporter->phone)
                            <p style="color: #9ca3af; font-size: 0.9rem;">{{ $ticket->reporter->phone }}</p>
                        @endif
                    </div>
                </div>

                <!-- Assigned Technician -->
                @if($ticket->assignedTo)
                    <div class="card">
                        <h2 class="card-title">Assigned Technician</h2>
                        <div class="reporter-info">
                            <div class="reporter-avatar">{{ $ticket->assignedTo->initials }}</div>
                            <div class="reporter-name">{{ $ticket->assignedTo->full_name }}</div>
                            <div class="reporter-role">{{ ucfirst(str_replace('-', ' ', $ticket->assignedTo->role)) }}</div>
                            @if($ticket->assignedTo->email)
                                <p style="color: #9ca3af; margin-top: 0.5rem; font-size: 0.9rem;">{{ $ticket->assignedTo->email }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="card">
                        <h2 class="card-title">Assignment Status</h2>
                        <div style="text-align: center; padding: 1rem 0; color: #9ca3af;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                            <p>Not yet assigned</p>
                            <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-primary" style="margin-top: 1rem;">Assign Now</a>
                        </div>
                    </div>
                @endif

                <!-- Admin Actions -->
                <div class="card">
                    <h2 class="card-title">Admin Actions</h2>
                    <div class="action-buttons">
                        <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-primary">Edit Ticket</a>
                        
                        @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                            <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary">Mark as Resolved</button>
                            </form>
                        @endif

                        <form action="{{ route('admin.tickets.destroy', $ticket->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Ticket</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection