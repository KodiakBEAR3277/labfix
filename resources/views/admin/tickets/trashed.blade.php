@extends('layouts.app')

@section('title', 'Deleted Tickets')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('admin.tickets.index') }}" class="back-btn">← Back to Ticket Management</a>

        <div class="page-header">
            <div>
                <h1>Deleted Tickets</h1>
                <p style="color: #9ca3af;">View and restore cancelled tickets</p>
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

        <!-- Info Banner -->
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; color: #ef4444;">
                <span style="font-size: 1.5rem;">🗑️</span>
                <div style="flex: 1;">
                    <strong>Deleted Tickets Archive:</strong> These tickets were cancelled by users before assignment. You can restore them or permanently delete them.
                </div>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Issue</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Deleted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="ticket-id">{{ $ticket->ticket_number }}</td>
                            <td>
                                <strong>{{ $ticket->title }}</strong>
                            </td>
                            <td>{{ $ticket->reporter->full_name }}</td>
                            <td>{{ $ticket->equipment->lab->name }}, {{ $ticket->equipment->equipment_code }}</td>
                            <td>{{ ucfirst($ticket->category) }}</td>
                            <td>
                                <span class="priority-badge priority-{{ $ticket->priority }}">
                                    {{ $ticket->priority === 'high' ? '🔴' : ($ticket->priority === 'medium' ? '🟡' : '🟢') }} 
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>{{ $ticket->deleted_at->diffForHumans() }}</td>
                            <td>
                                <div class="action-menu">
                                    <form action="{{ route('admin.tickets.restore', $ticket->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="action-btn" style="color: #10b981;">Restore</button>
                                    </form>
                                    <form action="{{ route('admin.tickets.force-delete', $ticket->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure? This will PERMANENTLY delete this ticket and cannot be undone!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn" style="color: #ef4444;">Delete Forever</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                <div style="font-size: 2rem; margin-bottom: 1rem;">✓</div>
                                <h3 style="margin-bottom: 0.5rem; color: #d1d5db;">No Deleted Tickets</h3>
                                <p>All clear! There are no cancelled tickets in the trash.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($tickets->hasPages())
                <div class="pagination">
                    <div class="page-info">
                        Showing {{ $tickets->firstItem() ?? 0 }}-{{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} deleted tickets
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

        <!-- Quick Stats -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #ef4444; margin-bottom: 0.5rem; font-size: 1rem;">Total Deleted</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #ffffff;">
                    {{ $tickets->total() }}
                </div>
                <p style="color: #9ca3af; font-size: 0.9rem; margin-top: 0.5rem;">
                    Tickets in trash
                </p>
            </div>

            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #3b82f6; margin-bottom: 0.5rem; font-size: 1rem;">Storage Info</h3>
                <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                    Deleted tickets are kept in the system for recovery. You can permanently delete them to free up space.
                </p>
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1.5rem; border-radius: 8px;">
                <h3 style="color: #10b981; margin-bottom: 0.5rem; font-size: 1rem;">Restore Options</h3>
                <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6;">
                    Restoring a ticket will make it active again and visible to all users.
                </p>
            </div>
        </div>
    </div>
@endsection