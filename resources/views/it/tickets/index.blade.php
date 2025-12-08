@extends('layouts.app')

@section('title', 'Ticket Queue')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Ticket Queue</h1>
            <p>View available tickets and manage your assignments</p>
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

        <!-- Info Banner for IT Support -->
        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; color: #60a5fa;">
                <span style="font-size: 1.5rem;">ℹ️</span>
                <div style="flex: 1;">
                    <strong>IT Support View:</strong> You can view all tickets and assign unassigned tickets to yourself. 
                    <a href="{{ route('it.assignments.index') }}" style="color: #2dd4bf; text-decoration: none; font-weight: 600;">View My Assignments →</a>
                </div>
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

        <!-- Tickets Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
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
                                    @if($ticket->assigned_to === auth()->id())
                                        <span style="color: #2dd4bf; font-weight: 600;">You</span>
                                    @else
                                        {{ $ticket->assignedTo->full_name }}
                                    @endif
                                @else
                                    <span style="color: #9ca3af;">Unassigned</span>
                                @endif
                            </td>
                            <td>{{ $ticket->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="action-menu">
                                    <a href="{{ route('it.tickets.show', $ticket->id) }}" class="action-btn">View</a>
                                    
                                    @if($ticket->assigned_to === auth()->id())
                                        {{-- If assigned to current user, they can edit via assignments --}}
                                        <a href="{{ route('it.assignments.edit', $ticket->id) }}" class="action-btn" style="color: #2dd4bf;">Update</a>
                                    @elseif(!$ticket->assigned_to)
                                        {{-- If unassigned, IT support can assign to self --}}
                                        <form action="{{ route('it.tickets.assign-self', $ticket->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="action-btn" style="color: #2dd4bf; border: none; background: none; cursor: pointer; padding: 0;">
                                                Assign to Me
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem; color: #9ca3af;">
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

        <!-- Quick Stats Panel -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div style="background: rgba(45, 212, 191, 0.1); border: 1px solid rgba(45, 212, 191, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #2dd4bf; margin-bottom: 0.5rem; font-size: 1rem;">My Active Tickets</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #ffffff;">
                    {{ \App\Models\Report::where('assigned_to', auth()->id())->open()->count() }}
                </div>
                <a href="{{ route('it.assignments.index') }}" style="color: #2dd4bf; text-decoration: none; font-size: 0.9rem;">
                    View My Assignments →
                </a>
            </div>

            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #ef4444; margin-bottom: 0.5rem; font-size: 1rem;">Unassigned Tickets</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #ffffff;">
                    {{ \App\Models\Report::whereNull('assigned_to')->open()->count() }}
                </div>
                <a href="{{ route('it.tickets.index', ['status' => 'new']) }}" style="color: #ef4444; text-decoration: none; font-size: 0.9rem;">
                    View Unassigned →
                </a>
            </div>

            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #3b82f6; margin-bottom: 0.5rem; font-size: 1rem;">High Priority</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #ffffff;">
                    {{ \App\Models\Report::where('priority', 'high')->open()->count() }}
                </div>
                <a href="{{ route('it.tickets.index', ['priority' => 'high']) }}" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">
                    View High Priority →
                </a>
            </div>
        </div>
    </div>
@endsection