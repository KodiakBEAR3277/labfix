@extends('layouts.app')

@section('title', 'My Reports')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>My Reports</h1>
            <a href="{{ route('user.reports.create') }}" class="new-report-btn">+ New Report</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Reports</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ $stats['active'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Resolved</div>
                <div class="stat-value">{{ $stats['resolved'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Resolution Time</div>
                <div class="stat-value">{{ $stats['avg_resolution_time'] }}<span style="font-size: 1rem; color: #9ca3af;"> hrs</span></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('user.reports.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        name="search"
                        placeholder="Search reports..."
                        value="{{ request('search') }}"
                        onchange="document.getElementById('filterForm').submit()"
                    >
                </div>
                <div class="filter-group">
                    <button type="submit" name="status" value="all" class="filter-btn {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">All</button>
                    <button type="submit" name="status" value="active" class="filter-btn {{ request('status') === 'active' ? 'active' : '' }}">Active</button>
                    <button type="submit" name="status" value="resolved" class="filter-btn {{ request('status') === 'resolved' ? 'active' : '' }}">Resolved</button>
                    <button type="submit" name="status" value="closed" class="filter-btn {{ request('status') === 'closed' ? 'active' : '' }}">Closed</button>
                </div>
            </div>
        </form>

        <!-- Reports Table -->
        <div class="reports-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Issue</th>
                        <th>Location</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td class="ticket-id">{{ $report->ticket_number }}</td>
                            <td>{{ $report->title }}</td>
                            <td>{{ $report->equipment->lab->name }}</td>
                            <td>{{ $report->equipment->equipment_code }}</td>
                            <td><span class="status-badge status-{{ $report->status_color }}">{{ ucfirst(str_replace('-', ' ', $report->status)) }}</span></td>
                            <td>
                                <span class="priority-badge priority-{{ $report->priority }}">
                                    {{ $report->priority === 'high' ? '🔴' : ($report->priority === 'medium' ? '🟡' : '🟢') }} 
                                    {{ ucfirst($report->priority) }}
                                </span>
                            </td>
                            <td>{{ $report->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="action-menu">
                                    <a href="{{ route('user.reports.show', $report->id) }}" class="action-btn">View</a>
                                    
                                    @if(!$report->assigned_to)
                                        {{-- Only show Edit and Delete if not yet assigned --}}
                                        <a href="{{ route('user.reports.edit', $report->id) }}" class="action-btn" style="color: #3b82f6;">Edit</a>
                                        <form action="{{ route('user.reports.destroy', $report->id) }}" class="action-btn" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this ticket?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn" style="color: #ef4444; border: none; background: none; cursor: pointer; padding: 0; font: inherit;">Cancel</button>
                                        </form>
                                    @else
                                        {{-- Show indicator that it's assigned --}}
                                        <span class="action-btn" style="opacity: 0.5; cursor: not-allowed;" title="Cannot edit - ticket is assigned">
                                            🔒 Locked
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                @if(request('search') || request('status'))
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No reports found</h3>
                                    <p>Try adjusting your filters or search terms</p>
                                    <a href="{{ route('user.reports.index') }}" class="btn btn-primary" style="margin-top: 1rem;">Clear Filters</a>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No reports yet</h3>
                                    <p>Report your first issue to get started</p>
                                    <a href="{{ route('user.reports.create') }}" class="btn btn-primary" style="margin-top: 1rem;">+ Report Issue</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($reports->hasPages())
                <div class="pagination">
                    <div class="page-info">
                        Showing {{ $reports->firstItem() ?? 0 }}-{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }} reports
                    </div>
                    <div class="page-controls">
                        @if ($reports->onFirstPage())
                            <button class="page-btn" disabled>← Previous</button>
                        @else
                            <a href="{{ $reports->previousPageUrl() }}" class="page-btn">← Previous</a>
                        @endif

                        @foreach ($reports->getUrlRange(1, $reports->lastPage()) as $page => $url)
                            @if ($page == $reports->currentPage())
                                <button class="page-btn active">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($reports->hasMorePages())
                            <a href="{{ $reports->nextPageUrl() }}" class="page-btn">Next →</a>
                        @else
                            <button class="page-btn" disabled>Next →</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Info Banner -->
        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1rem; border-radius: 8px; margin-top: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; color: #3b82f6;">
                <span style="font-size: 1.5rem;">ℹ️</span>
                <div style="flex: 1;">
                    <strong>Note:</strong> You can edit or cancel tickets before they're assigned to a technician. Once assigned, contact the technician to make changes.
                </div>
            </div>
        </div>
    </div>
@endsection