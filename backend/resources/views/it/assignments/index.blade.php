@extends('layouts.app')

@section('title', 'My Assignments')

@section('navigation')
    <x-nav.it />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>My Assignments</h1>
            <p>Tickets currently assigned to you</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Assigned</div>
                <div class="stat-value">{{ $stats['total_assigned'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">High Priority</div>
                <div class="stat-value">{{ $stats['high_priority'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">{{ $stats['completed_today'] }}</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <form method="GET" action="{{ route('it.assignments.index') }}">
            <div class="filter-tabs">
                <button type="submit" name="status" value="" class="tab {{ !request('status') ? 'active' : '' }}">
                    All Assignments ({{ $stats['total_assigned'] }})
                </button>
                <button type="submit" name="status" value="in-progress" class="tab {{ request('status') === 'in-progress' ? 'active' : '' }}">
                    In Progress ({{ $stats['in_progress'] }})
                </button>
                <button type="submit" name="status" value="active" class="tab {{ request('status') === 'active' ? 'active' : '' }}">
                    High Priority ({{ $stats['high_priority'] }})
                </button>
            </div>
        </form>

        <!-- Tickets Grid -->
        <div class="tickets-grid">
            @forelse($tickets as $ticket)
                <div class="ticket-card {{ $ticket->priority === 'high' ? 'priority-high' : '' }}" onclick="window.location.href='{{ route('it.assignments.show', $ticket->id) }}'" style="cursor: pointer;">
                    <div class="ticket-header">
                        <div>
                            <div class="ticket-id">{{ $ticket->ticket_number }}</div>
                            <h3 class="ticket-title">{{ $ticket->title }}</h3>
                        </div>
                        <span class="priority-badge priority-{{ $ticket->priority }}">
                            {{ $ticket->priority === 'high' ? '🔴' : ($ticket->priority === 'medium' ? '🟡' : '🟢') }} {{ ucfirst($ticket->priority) }}
                        </span>
                    </div>
                    <div class="ticket-meta">
                        <div class="meta-item">
                            <span>👤</span> {{ $ticket->reporter->full_name }}
                        </div>
                        <div class="meta-item">
                            <span>📍</span> {{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}
                        </div>
                        <div class="meta-item">
                            <span>🕒</span> {{ $ticket->created_at->diffForHumans() }}
                        </div>
                        <div class="meta-item">
                            <span>🏷️</span> {{ ucfirst($ticket->category) }}
                        </div>
                    </div>
                    <p class="ticket-description">
                        {{ Str::limit($ticket->description, 150) }}
                    </p>
                    <div class="ticket-footer">
                        <span class="status-badge status-{{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                        <div class="ticket-actions" onclick="event.stopPropagation();">
                            <a href="{{ route('it.assignments.show', $ticket->id) }}" class="action-btn">View Details</a>
                            <a href="{{ route('it.assignments.edit', $ticket->id) }}" class="action-btn">Update Status</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No assignments yet</h3>
                    <p>You don't have any tickets assigned to you at the moment</p>
                    <a href="{{ route('it.tickets.index') }}" class="btn btn-primary" style="margin-top: 1rem;">View Ticket Queue</a>
                </div>
            @endforelse
        </div>

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
@endsection