@extends('layouts.app')

@section('title', 'Transaction History')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.tickets.index') }}" class="back-btn">← Back to Ticket Management</a>
        
        <div class="page-header">
            <div>
                <h1>Transaction History</h1>
                <p style="color: #9ca3af;">Complete audit trail of all ticket changes</p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Today</div>
                <div class="stat-value">{{ $stats['today'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Week</div>
                <div class="stat-value">{{ $stats['this_week'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Month</div>
                <div class="stat-value">{{ $stats['this_month'] }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('admin.transactions.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="filter-row">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search by ticket, user, or action..."
                            value="{{ request('search') }}"
                            onchange="document.getElementById('filterForm').submit()"
                        >
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Action:</span>
                        <select name="action" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('action') === 'all' || !request('action') ? 'selected' : '' }}>All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $action)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">User:</span>
                        <select name="user" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ request('user') === 'all' || !request('user') ? 'selected' : '' }}>All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">From:</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="document.getElementById('filterForm').submit()">
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">To:</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="document.getElementById('filterForm').submit()">
                    </div>
                </div>
            </div>
        </form>

        <!-- Transactions Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Ticket</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Changes</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td style="white-space: nowrap;">{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                            <td>
                                @if($transaction->ticket)
                                    <a href="{{ route('admin.tickets.show', $transaction->ticket->id) }}" style="color: #2dd4bf; text-decoration: none;">
                                        {{ $transaction->ticket->ticket_number }}
                                    </a>
                                @else
                                    <span style="color: #9ca3af;">Deleted Ticket</span>
                                @endif
                            </td>
                            <td>
                                <span class="action-badge action-{{ str_replace('_', '-', $transaction->action) }}">
                                    @switch($transaction->action)
                                        @case('created')
                                            🎫 Created
                                            @break
                                        @case('status_changed')
                                            🔄 Status
                                            @break
                                        @case('assigned')
                                            👤 Assigned
                                            @break
                                        @case('priority_changed')
                                            ⚠️ Priority
                                            @break
                                        @case('updated')
                                            ✏️ Updated
                                            @break
                                        @case('deleted')
                                            🗑️ Deleted
                                            @break
                                        @case('restored')
                                            ♻️ Restored
                                            @break
                                        @default
                                            📌 {{ ucfirst(str_replace('_', ' ', $transaction->action)) }}
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                @if($transaction->user)
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #2dd4bf, #14b8a6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                            {{ $transaction->user->initials }}
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;">{{ $transaction->user->full_name }}</div>
                                            <div style="font-size: 0.8rem; color: #9ca3af;">{{ ucfirst(str_replace('-', ' ', $transaction->user->role)) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">System</span>
                                @endif
                            </td>
                            <td>
                                @if($transaction->old_value || $transaction->new_value)
                                    <div style="font-size: 0.85rem;">
                                        @if($transaction->old_value)
                                            <span style="color: #ef4444;">{{ $transaction->old_value }}</span>
                                            <span style="color: #9ca3af;"> → </span>
                                        @endif
                                        @if($transaction->new_value)
                                            <span style="color: #10b981;">{{ $transaction->new_value }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">-</span>
                                @endif
                            </td>
                            <td>{{ $transaction->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                @if(request()->hasAny(['search', 'action', 'user', 'date_from', 'date_to']))
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No transactions found</h3>
                                    <p>Try adjusting your filters or search terms</p>
                                    <a href="{{ route('admin.transactions.index') }}" class="btn btn-primary" style="margin-top: 1rem;">Clear Filters</a>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
                                    <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No transactions yet</h3>
                                    <p>Transaction history will appear here as tickets are updated</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="pagination">
                    <div class="page-info">
                        Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
                    </div>
                    <div class="page-controls">
                        @if ($transactions->onFirstPage())
                            <button class="page-btn" disabled>← Previous</button>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="page-btn">← Previous</a>
                        @endif

                        @foreach ($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                            @if ($page == $transactions->currentPage())
                                <button class="page-btn active">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="page-btn">Next →</a>
                        @else
                            <button class="page-btn" disabled>Next →</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Info Card -->
        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; color: #60a5fa;">
                <span style="font-size: 1.5rem;">ℹ️</span>
                <div style="flex: 1;">
                    <strong>About Transaction History:</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #d1d5db;">
                        This is a complete, read-only audit trail of all changes made to tickets in the system. Transactions are automatically logged and cannot be manually edited or deleted to maintain data integrity.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.action-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.action-created { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.action-status-changed { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.action-assigned { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
.action-priority-changed { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.action-updated { background: rgba(45, 212, 191, 0.2); color: #2dd4bf; }
.action-deleted { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.action-restored { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
</style>
@endpush