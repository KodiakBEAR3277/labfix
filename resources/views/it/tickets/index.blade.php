@extends('layouts.app')

@section('title', 'Ticket Queue')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Ticket Queue</h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="exportTickets()">Export</button>
                <button class="btn btn-primary" onclick="goToBulkActions()">Bulk Actions</button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Open Tickets</div>
                <div class="stat-value">{{ $stats['open'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Assigned</div>
                <div class="stat-value">{{ $stats['assigned'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">High Priority</div>
                <div class="stat-value">{{ $stats['high_priority'] }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('it.tickets.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="filter-row">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search tickets by ID, title, or location..."
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
            <span class="bulk-text"><span id="selectedCount">0</span> tickets selected</span>
            <button class="btn btn-secondary" onclick="bulkAssignToMe()">Assign to Me</button>
            <button class="btn btn-secondary" onclick="goToBulkActions()">More Actions</button>
            <button class="btn btn-secondary" onclick="clearSelection()">Clear Selection</button>
        </div>

        <!-- Tickets Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" class="ticket-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
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
                            <td><input type="checkbox" class="ticket-checkbox ticket-select" value="{{ $ticket->id }}" onchange="updateBulkBar()"></td>
                            <td class="ticket-id">{{ $ticket->formatted_id }}</td>
                            <td>{{ $ticket->title }}</td>
                            <td>{{ $ticket->reporter->full_name }}</td>
                            <td>{{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}</td>
                            <td><span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span></td>
                            <td><span class="priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                            <td>{{ $ticket->assignedTo ? $ticket->assignedTo->full_name : 'Unassigned' }}</td>
                            <td>{{ $ticket->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="action-menu">
                                    <a href="{{ route('it.tickets.show', $ticket->id) }}" class="action-btn">View</a>
                                    @if(!$ticket->assigned_to)
                                        <form action="{{ route('it.tickets.assign-self', $ticket->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="action-btn">Assign</button>
                                        </form>
                                    @else
                                        <a href="{{ route('it.tickets.show', $ticket->id) }}" class="action-btn">Update</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                @if(request()->hasAny(['search', 'status', 'priority', 'lab']))
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No tickets found</h3>
                                    <p>Try adjusting your filters or search terms</p>
                                    <a href="{{ route('it.tickets.index') }}" class="btn btn-primary" style="margin-top: 1rem;">Clear Filters</a>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No tickets yet</h3>
                                    <p>All clear! No tickets in the queue.</p>
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

    // Bulk assign to current user
    function bulkAssignToMe() {
        const selected = Array.from(document.querySelectorAll('.ticket-select:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one ticket');
            return;
        }
        
        // Create a form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("it.tickets.bulk-update") }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        const action = document.createElement('input');
        action.type = 'hidden';
        action.name = 'action';
        action.value = 'assign';
        form.appendChild(action);
        
        const assignedTo = document.createElement('input');
        assignedTo.type = 'hidden';
        assignedTo.name = 'assigned_to';
        assignedTo.value = '{{ auth()->id() }}';
        form.appendChild(assignedTo);
        
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ticket_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }

    // Go to bulk actions page
    function goToBulkActions() {
        const selected = Array.from(document.querySelectorAll('.ticket-select:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one ticket');
            return;
        }
        window.location.href = '{{ route("it.tickets.bulk") }}?ids=' + selected.join(',');
    }

    // Export tickets (placeholder)
    function exportTickets() {
        alert('Export functionality coming soon!');
    }
</script>
@endpush