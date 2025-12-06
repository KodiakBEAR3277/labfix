@extends('layouts.app')

@section('title', 'Edit Ticket')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('it.tickets.show', $ticket->id) }}" class="back-btn">← Back to Ticket</a>

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
                    
                    <form action="{{ route('it.tickets.update', $ticket->id) }}" method="POST">
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
                                            {{ $staff->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="help-text">Assign this ticket to an IT technician</p>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Update Ticket & Notify User</button>
                            <a href="{{ route('it.tickets.show', $ticket->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Quick Status Actions -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Quick Actions</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">Apply common status changes with one click</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        @if($ticket->status !== 'in-progress')
                            <form action="{{ route('it.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in-progress">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Mark In Progress</button>
                            </form>
                        @endif

                        @if($ticket->status !== 'resolved')
                            <form action="{{ route('it.tickets.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Mark Resolved</button>
                            </form>
                        @endif

                        @if(!$ticket->assigned_to || $ticket->assigned_to !== auth()->id())
                            <form action="{{ route('it.tickets.assign-self', $ticket->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Assign to Me</button>
                            </form>
                        @endif
                    </div>
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
                    </div>
                </div>

                <!-- Description Preview -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Problem Description</h2>
                    <p style="color: #d1d5db; line-height: 1.6;">
                        {{ Str::limit($ticket->description, 200) }}
                    </p>
                    <a href="{{ route('it.tickets.show', $ticket->id) }}" class="btn btn-secondary" style="margin-top: 1rem;">View Full Details</a>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection