@extends('layouts.app')

@section('title', 'Ticket Management')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Ticket Management</h1>
                <p style="color: #9ca3af;">Oversee all support tickets and manage assignments</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="exportTickets()">📊 Export Report</button>
                <button class="btn btn-primary" onclick="goToBulkActions()">⚡ Bulk Actions</button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="system-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Tickets</span>
                    <span class="stat-icon">📋</span>
                </div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-detail">All time</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Open Tickets</span>
                    <span class="stat-icon">🔓</span>
                </div>
                <div class="stat-value">{{ $stats['open'] }}</div>
                <div class="stat-detail">Needs attention</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Unassigned</span>
                    <span class="stat-icon">⏳</span>
                </div>
                <div class="stat-value">{{ $stats['unassigned'] }}</div>
                <div class="stat-detail">Waiting for assignment</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">In Progress</span>
                    <span class="stat-icon">⚙️</span>
                </div>
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
                <div class="stat-detail">Being worked on</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">High Priority</span>
                    <span class="stat-icon">🔴</span>
                </div>
                <div class="stat-value">{{ $stats['high_priority'] }}</div>
                <div class="stat-detail">Urgent attention needed</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Resolved</span>
                    <span class="stat-icon">✅</span>
                </div>
                <div class="stat-value">{{ $stats['resolved'] }}</div>
                <div class="stat-detail">Completed tickets</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('admin.tickets.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="filter-row">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search by ticket ID, title, reporter, or location..."
                            value="{{ request('search') }}"
                            onchange="document.getElementById('filterForm').submit()"
                        >
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Status:</span>
                        <select name="status" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>All Status</option>
                            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in-progress" {{ request('status') === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Priority:</span>
                        <select name="priority" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('priority') === 'all' || !request('priority') ? 'selected' : '' }}>All Priority</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Assigned:</span>
                        <select name="assigned" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('assigned') === 'all' || !request('assigned') ? 'selected' : '' }}>All</option>
                            <option value="unassigned" {{ request('assigned') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                            @foreach($itStaff as $staff)
                                <option value="{{ $staff->id }}" {{ request('assigned') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Lab:</span>
                        <select name="lab" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('lab') === 'all' || !request('lab') ? 'selected' : '' }}>All Labs</option>
                            @foreach($labs as $lab)
                                <option value="{{ $lab }}" {{ request('lab') === $lab ? 'selected' : '' }}>{{ $lab }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <!-- Bulk Actions Bar (shown when tickets are selected) -->
        <div class="bulk-actions" id="bulkActionsBar" style="display: none;">
            <span class="bulk-text">
                <span id="selectedCount">0</span> tickets selected
            </span>
            <button class="btn btn-white" onclick="quickAssign()">Quick Assign</button>
            <button class="btn btn-white" onclick="goToBulkActions()">More Actions</button>
            <button class="btn btn-secondary" onclick="clearSelection()">Clear</button>
        </div>

        <!-- Tickets Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="ticket-checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Ticket ID</th>
                        <th>Issue</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>
                                <input type="checkbox" class="ticket-checkbox ticket-select" value="{{ $ticket->id }}" onchange="updateBulkBar()">
                            </td>
                            <td class="ticket-id">{{ $ticket->ticket_number }}</td>
                            <td>
                                <strong>{{ $ticket->title }}</strong>
                                <br>
                                <small style="color: #9ca3af;">{{ ucfirst($ticket->category) }}</small>
                            </td>
                            <td>{{ $ticket->reporter->full_name }}</td>
                            <td>{{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}</td>
                            <td>
                                <span class="status-badge status-{{ $ticket->status_color }}">
                                    {{ ucfirst(str_replace('-', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="priority-badge priority-{{ $ticket->priority }}">
                                    {{ $ticket->priority === 'high' ? '🔴' : ($ticket->priority === 'medium' ? '🟡' : '🟢') }} 
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->assignedTo)
                                    {{ $ticket->assignedTo->full_name }}
                                @else
                                    <span style="color: #9ca3af;">Unassigned</span>
                                @endif
                            </td>
                            <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="action-menu">
                                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="action-btn">View</a>
                                    <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="action-btn">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                @if(request()->hasAny(['search', 'status', 'priority', 'lab', 'assigned']))
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No tickets found</h3>
                                    <p>Try adjusting your filters or search terms</p>
                                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-primary" style="margin-top: 1rem;">Clear Filters</a>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No tickets yet</h3>
                                    <p>All clear! No tickets in the system.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($tickets->hasPages())
                <div class="pagination">
                    <div class="page-info">
                        Showing {{ $tickets->firstItem() ?? 0 }}-{{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} tickets
                    </div>
                    <div class="page-controls">
                        @if ($tickets->onFirstPage())
                            <button class="page-btn" disabled>← Previous</button>
                        @else
                            <a href="{{ $tickets->previousPageUrl() }}" class="page-btn">← Previous</a>
                        @endif

                        @foreach ($tickets->getUrlRange(1, $tickets->lastPage()) as $page => $url)
                            @if ($page == $tickets->currentPage())
                                <button class="page-btn active">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($tickets->hasMorePages())
                            <a href="{{ $tickets->nextPageUrl() }}" class="page-btn">Next →</a>
                        @else
                            <button class="page-btn" disabled>Next →</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Assign Modal -->
    <div class="modal-overlay" id="quickAssignModal">
        <div class="modal">
            <h2 class="modal-header">Quick Assign</h2>
            <form id="quickAssignForm" method="POST" action="{{ route('admin.tickets.bulk-update') }}">
                @csrf
                <input type="hidden" name="action" value="assign">
                <div id="selectedTicketsContainer"></div>
                
                <div class="form-group">
                    <label>Assign To *</label>
                    <select name="assigned_to" required>
                        <option value="">Select Technician</option>
                        @foreach($itStaff as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->full_name }} ({{ ucfirst(str_replace('-', ' ', $staff->role)) }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Assign Selected Tickets</button>
                    <button type="button" class="btn btn-cancel" onclick="closeQuickAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Toggle select all checkboxes
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.ticket-select');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkBar();
    }

    // Update bulk actions bar visibility
    function updateBulkBar() {
        const selected = document.querySelectorAll('.ticket-select:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const countSpan = document.getElementById('selectedCount');
        
        if (selected.length > 0) {
            bulkBar.style.display = 'flex';
            countSpan.textContent = selected.length;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    // Clear selection
    function clearSelection() {
        document.querySelectorAll('.ticket-select').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateBulkBar();
    }

    // Quick assign modal
    function quickAssign() {
        const selected = Array.from(document.querySelectorAll('.ticket-select:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one ticket');
            return;
        }
        
        // Add hidden inputs for selected tickets
        const container = document.getElementById('selectedTicketsContainer');
        container.innerHTML = '';
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ticket_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        
        document.getElementById('quickAssignModal').classList.add('active');
    }

    function closeQuickAssignModal() {
        document.getElementById('quickAssignModal').classList.remove('active');
    }

    // Go to bulk actions page
    function goToBulkActions() {
        const selected = Array.from(document.querySelectorAll('.ticket-select:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one ticket');
            return;
        }
        window.location.href = '{{ route("admin.tickets.bulk") }}?ids=' + selected.join(',');
    }

    // Export tickets (placeholder)
    function exportTickets() {
        alert('Export functionality coming soon! This will export the current filtered view to CSV/Excel.');
    }

    // Close modal when clicking outside
    document.getElementById('quickAssignModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeQuickAssignModal();
    });
</script>
@endpush