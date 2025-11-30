@extends('layouts.app')

@section('title', 'Bulk Operations')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Bulk Operations</h1>
            <p>Apply actions to multiple tickets simultaneously</p>
        </div>

        <!-- Selection Bar -->
        <div class="selection-bar">
            <div class="selection-info">
                <div class="selection-count">{{ $selectedTickets->count() }} Tickets Selected</div>
                <div>Ready for bulk operations</div>
            </div>
            <div class="selection-actions">
                <a href="{{ route('it.tickets.index') }}" class="btn btn-outline">Back to Queue</a>
            </div>
        </div>

        <!-- Operations Grid -->
        <div class="operations-grid">
            <!-- Assign Tickets -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">👤</div>
                    <h3 class="operation-title">Assign Tickets</h3>
                </div>
                <p class="operation-description">
                    Assign all selected tickets to a specific technician
                </p>
                <form action="{{ route('it.tickets.bulk-update') }}" method="POST">
                    @csrf
                    @foreach($selectedTickets as $ticket)
                        <input type="hidden" name="ticket_ids[]" value="{{ $ticket->id }}">
                    @endforeach
                    <input type="hidden" name="action" value="assign">
                    
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to" required>
                            <option value="">Select Technician</option>
                            @foreach($itStaff as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign All</button>
                </form>
            </div>

            <!-- Update Status -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">🔄</div>
                    <h3 class="operation-title">Update Status</h3>
                </div>
                <p class="operation-description">
                    Change the status of all selected tickets
                </p>
                <form action="{{ route('it.tickets.bulk-update') }}" method="POST">
                    @csrf
                    @foreach($selectedTickets as $ticket)
                        <input type="hidden" name="ticket_ids[]" value="{{ $ticket->id }}">
                    @endforeach
                    <input type="hidden" name="action" value="status">
                    
                    <div class="form-group">
                        <label>New Status</label>
                        <select name="status" required>
                            <option value="">Select Status</option>
                            <option value="assigned">Assigned</option>
                            <option value="in-progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update All</button>
                </form>
            </div>

            <!-- Change Priority -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">⚠️</div>
                    <h3 class="operation-title">Change Priority</h3>
                </div>
                <p class="operation-description">
                    Adjust priority level for all selected tickets
                </p>
                <form action="{{ route('it.tickets.bulk-update') }}" method="POST">
                    @csrf
                    @foreach($selectedTickets as $ticket)
                        <input type="hidden" name="ticket_ids[]" value="{{ $ticket->id }}">
                    @endforeach
                    <input type="hidden" name="action" value="priority">
                    
                    <div class="form-group">
                        <label>Priority Level</label>
                        <select name="priority" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Priority</button>
                </form>
            </div>

            <!-- Close Tickets -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">✅</div>
                    <h3 class="operation-title">Close Tickets</h3>
                </div>
                <p class="operation-description">
                    Mark all selected tickets as closed
                </p>
                <form action="{{ route('it.tickets.bulk-update') }}" method="POST" onsubmit="return confirm('Are you sure you want to close these {{ $selectedTickets->count() }} tickets?')">
                    @csrf
                    @foreach($selectedTickets as $ticket)
                        <input type="hidden" name="ticket_ids[]" value="{{ $ticket->id }}">
                    @endforeach
                    <input type="hidden" name="action" value="close">
                    
                    <button type="submit" class="btn btn-primary">Close All</button>
                </form>
            </div>
        </div>

        <!-- Selected Tickets Preview -->
        <div class="preview-section">
            <h2 class="preview-title">Selected Tickets ({{ $selectedTickets->count() }})</h2>
            <div class="tickets-list">
                @foreach($selectedTickets as $ticket)
                    <div class="ticket-preview">
                        <div class="ticket-info">
                            <h4>{{ $ticket->formatted_id }} - {{ $ticket->title }}</h4>
                            <p>{{ $ticket->lab_location }}{{ $ticket->equipment_id ? ', ' . $ticket->equipment_id : '' }} • {{ ucfirst($ticket->priority) }} Priority</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection