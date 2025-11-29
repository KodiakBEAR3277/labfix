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
                <div class="selection-count">5 Tickets Selected</div>
                <div>Ready for bulk operations</div>
            </div>
            <div class="selection-actions">
                <button class="btn btn-white">Clear Selection</button>
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
                <div class="form-group">
                    <label>Assign To</label>
                    <select>
                        <option>Select Technician</option>
                        <option>Mike Chen</option>
                        <option>Sarah Lee</option>
                        <option>Tom Anderson</option>
                        <option>Assign to Me</option>
                    </select>
                </div>
                <button class="btn btn-primary">Assign All</button>
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
                <div class="form-group">
                    <label>New Status</label>
                    <select>
                        <option>Select Status</option>
                        <option>Assigned</option>
                        <option>In Progress</option>
                        <option>Waiting for Parts</option>
                        <option>Resolved</option>
                    </select>
                </div>
                <button class="btn btn-primary">Update All</button>
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
                <div class="form-group">
                    <label>Priority Level</label>
                    <select>
                        <option>Select Priority</option>
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Critical</option>
                    </select>
                </div>
                <button class="btn btn-primary">Update Priority</button>
            </div>

            <!-- Add Note -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">📝</div>
                    <h3 class="operation-title">Add Bulk Note</h3>
                </div>
                <p class="operation-description">
                    Add an internal note to all selected tickets
                </p>
                <div class="form-group">
                    <label>Note</label>
                    <textarea placeholder="Enter note to add to all tickets..."></textarea>
                </div>
                <button class="btn btn-primary">Add to All</button>
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
                <div class="form-group">
                    <label>Resolution Note</label>
                    <textarea placeholder="Optional: Add resolution note..."></textarea>
                </div>
                <button class="btn btn-primary">Close All</button>
            </div>

            <!-- Send Notification -->
            <div class="operation-card">
                <div class="operation-header">
                    <div class="operation-icon">📧</div>
                    <h3 class="operation-title">Send Notification</h3>
                </div>
                <p class="operation-description">
                    Send a custom message to all ticket reporters
                </p>
                <div class="form-group">
                    <label>Message</label>
                    <textarea placeholder="Enter message to send to reporters..."></textarea>
                </div>
                <button class="btn btn-primary">Send to All</button>
            </div>
        </div>

        <!-- Selected Tickets Preview -->
        <div class="preview-section">
            <h2 class="preview-title">Selected Tickets (5)</h2>
            <div class="tickets-list">
                <div class="ticket-preview">
                    <div class="ticket-info">
                        <h4>#092 - Mouse not responding</h4>
                        <p>Lab B, PC-15 • High Priority</p>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>

                <div class="ticket-preview">
                    <div class="ticket-info">
                        <h4>#091 - Software installation fails</h4>
                        <p>Lab C, PC-20 • Medium Priority</p>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>

                <div class="ticket-preview">
                    <div class="ticket-info">
                        <h4>#090 - Monitor flickering</h4>
                        <p>Lab A, PC-08 • Medium Priority</p>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>

                <div class="ticket-preview">
                    <div class="ticket-info">
                        <h4>#088 - Keyboard keys stuck</h4>
                        <p>Lab A, PC-12 • Low Priority</p>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>

                <div class="ticket-preview">
                    <div class="ticket-info">
                        <h4>#086 - Slow system performance</h4>
                        <p>Lab C, PC-10 • Low Priority</p>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay">
        <div class="modal">
            <h2 class="modal-header">Confirm Bulk Operation</h2>
            <div class="modal-body">
                Are you sure you want to apply this action to 5 selected tickets? This action cannot be undone.
            </div>
            <div class="modal-actions">
                <button class="btn btn-cancel">Cancel</button>
                <button class="btn btn-confirm">Confirm</button>
            </div>
        </div>
    </div>
@endsection