@extends('layouts.app')

@section('title', 'Edit Ticket')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="back-btn">← Back to Ticket</a>

        <div class="page-header">
            <h1>Edit Ticket</h1>
            <p>Update ticket status, priority, and assignment</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="content-layout">
            <!-- Main Form -->
            <div>
                <!-- Ticket Summary -->
                <div class="card">
                    <h2 class="card-title">Ticket Summary</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Ticket Number</span>
                            <span class="info-value">{{ $ticket->ticket_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Title</span>
                            <span class="info-value">{{ $ticket->title }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Reporter</span>
                            <span class="info-value">{{ $ticket->reporter->full_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Location</span>
                            <span class="info-value">{{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category</span>
                            <span class="info-value">{{ ucfirst($ticket->category) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Update Ticket Details</h2>
                    
                    <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" required>
                                    <option value="new" {{ $ticket->status === 'new' ? 'selected' : '' }}>New</option>
                                    <option value="assigned" {{ $ticket->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                    <option value="in-progress" {{ $ticket->status === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                                <p class="help-text">Update the current status of this ticket</p>
                            </div>

                            <div class="form-group">
                                <label>Priority *</label>
                                <select name="priority" required>
                                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                </select>
                                <p class="help-text">Adjust priority based on urgency and impact</p>
                            </div>

                            <div class="form-group">
                                <label>Assign To</label>
                                <select name="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach($itStaff as $staff)
                                        <option value="{{ $staff->id }}" {{ $ticket->assigned_to == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->full_name }} ({{ ucfirst(str_replace('-', ' ', $staff->role)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="help-text">Assign this ticket to any IT technician or admin</p>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Update Ticket & Notify User</button>
                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Quick Status Actions -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Quick Actions</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">Apply common status changes with one click</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        @if($ticket->status !== 'assigned' && !$ticket->assigned_to)
                            <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="assigned">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Assign to Me</button>
                            </form>
                        @endif

                        @if($ticket->status !== 'in-progress')
                            <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in-progress">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Mark In Progress</button>
                            </form>
                        @endif

                        @if($ticket->status !== 'resolved')
                            <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Mark Resolved</button>
                            </form>
                        @endif

                        @if($ticket->status !== 'closed')
                            <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="closed">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Close Ticket</button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="card" style="margin-top: 1.5rem; border-color: rgba(239, 68, 68, 0.3);">
                    <h2 class="card-title" style="color: #ef4444;">Danger Zone</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">Irreversible actions - proceed with caution</p>
                    
                    <form action="{{ route('admin.tickets.destroy', $ticket->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket? This action cannot be undone!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete This Ticket Permanently</button>
                    </form>
                </div>
            </div>

            <!-- Sidebar - Context -->
            <div>
                <!-- Current Status -->
                <div class="card">
                    <h2 class="card-title">Current Status</h2>
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
                        <div class="info-item">
                            <span class="info-label">Assigned To</span>
                            <span class="info-value">{{ $ticket->assignedTo ? $ticket->assignedTo->full_name : 'Unassigned' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created</span>
                            <span class="info-value">{{ $ticket->created_at->diffForHumans() }}</span>
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
                    </div>
                </div>

                <!-- Description Preview -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Problem Description</h2>
                    <p style="color: #d1d5db; line-height: 1.6;">
                        {{ Str::limit($ticket->description, 200) }}
                    </p>
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-secondary" style="margin-top: 1rem;">View Full Details</a>
                </div>

                <!-- Equipment Info -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Equipment Status</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Equipment</span>
                            <span class="info-value">{{ $ticket->equipment->equipment_code }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Type</span>
                            <span class="info-value">{{ ucfirst($ticket->equipment->type) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $ticket->equipment->status === 'operational' ? 'active' : 'new' }}">
                                    {{ ucfirst(str_replace('-', ' ', $ticket->equipment->status)) }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lab</span>
                            <span class="info-value">{{ $ticket->equipment->lab->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reporter Info -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Reporter</h2>
                    <div class="reporter-info">
                        <div class="reporter-avatar">{{ $ticket->reporter->initials }}</div>
                        <div class="reporter-name">{{ $ticket->reporter->full_name }}</div>
                        <div class="reporter-role">{{ ucfirst(str_replace('-', ' ', $ticket->reporter->role)) }}</div>
                        @if($ticket->reporter->email)
                            <p style="color: #9ca3af; margin-top: 0.5rem; font-size: 0.9rem;">{{ $ticket->reporter->email }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection