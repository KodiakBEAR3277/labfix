@extends('layouts.app')

@section('title', 'Update Assignment')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('it.assignments.show', $ticket->id) }}" class="back-btn">← Back to Assignment</a>

        <div class="page-header">
            <h1>Update Assignment</h1>
            <p>Update your progress on this ticket</p>
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

                <!-- Update Form -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Update Progress</h2>
                    
                    <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" required>
                                    <option value="assigned" {{ $ticket->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                    <option value="in-progress" {{ $ticket->status === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                                <p class="help-text">Update the current status of your work on this ticket</p>
                            </div>

                            <div class="form-group">
                                <label>Priority *</label>
                                <select name="priority" required>
                                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                </select>
                                <p class="help-text">Adjust priority if needed based on your assessment</p>
                            </div>

                            <div class="info-card" style="background: rgba(45, 212, 191, 0.1); border: 1px solid rgba(45, 212, 191, 0.3); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                                <p style="color: #2dd4bf; margin: 0; font-size: 0.9rem;">
                                    💡 <strong>Note:</strong> The reporter will be automatically notified when you update this ticket.
                                </p>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Changes & Notify User</button>
                            <a href="{{ route('it.assignments.show', $ticket->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Quick Status Actions -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Quick Actions</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">Apply common status changes with one click</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        @if($ticket->status !== 'in-progress')
                            <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in-progress">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                    🚀 Start Working
                                </button>
                            </form>
                        @endif

                        @if($ticket->status !== 'resolved')
                            <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                    ✅ Mark Resolved
                                </button>
                            </form>
                        @endif

                        @if($ticket->priority !== 'high' && $ticket->status !== 'resolved')
                            <form action="{{ route('it.assignments.update', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="{{ $ticket->status }}">
                                <input type="hidden" name="priority" value="high">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                    🔴 Escalate Priority
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Work Guidelines -->
                <div class="card" style="margin-top: 1.5rem; background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                    <h2 class="card-title" style="color: #3b82f6;">📋 Status Guidelines</h2>
                    <div style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: #60a5fa;">Assigned:</strong> Ticket is assigned but work hasn't started yet
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: #60a5fa;">In Progress:</strong> You're actively working on resolving this issue
                        </div>
                        <div>
                            <strong style="color: #60a5fa;">Resolved:</strong> Issue is fixed and verified - reporter will be notified
                        </div>
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
                            <span class="info-label">Time on Task</span>
                            <span class="info-value">{{ $ticket->assigned_at ? $ticket->assigned_at->diffForHumans(null, true) : 'Just assigned' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Assigned</span>
                            <span class="info-value">{{ $ticket->assigned_at ? $ticket->assigned_at->format('M d, g:i A') : 'Recently' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Description Preview -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Problem Description</h2>
                    <p style="color: #d1d5db; line-height: 1.6;">
                        {{ Str::limit($ticket->description, 200) }}
                    </p>
                    <a href="{{ route('it.assignments.show', $ticket->id) }}" class="btn btn-secondary" style="margin-top: 1rem;">View Full Details</a>
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

                <!-- Reporter Contact -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Reporter Contact</h2>
                    <div class="reporter-info">
                        <div class="reporter-avatar">{{ $ticket->reporter->initials }}</div>
                        <div class="reporter-name">{{ $ticket->reporter->full_name }}</div>
                        <div class="reporter-role">{{ ucfirst(str_replace('-', ' ', $ticket->reporter->role)) }}</div>
                        @if($ticket->reporter->email)
                            <p style="color: #9ca3af; margin-top: 0.5rem; font-size: 0.9rem;">
                                <a href="mailto:{{ $ticket->reporter->email }}" style="color: #2dd4bf; text-decoration: none;">
                                    📧 {{ $ticket->reporter->email }}
                                </a>
                            </p>
                        @endif
                        @if($ticket->reporter->phone)
                            <p style="color: #9ca3af; font-size: 0.9rem;">
                                <a href="tel:{{ $ticket->reporter->phone }}" style="color: #2dd4bf; text-decoration: none;">
                                    📞 {{ $ticket->reporter->phone }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection