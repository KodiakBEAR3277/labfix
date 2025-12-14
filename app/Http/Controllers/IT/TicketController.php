<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsTicketTransactions;

class TicketController extends Controller
{
    use LogsTicketTransactions;
    
    // Show all tickets (IT Queue)
    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'assignedTo', 'equipment.lab']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('equipment', function($q) use ($search) {
                      $q->where('equipment_code', 'like', "%{$search}%")
                        ->orWhereHas('lab', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                  })
                  ->orWhereHas('reporter', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Priority filter
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Lab filter
        if ($request->filled('lab') && $request->lab !== 'all') {
            $query->whereHas('equipment.lab', function($q) use ($request) {
                $q->where('name', $request->lab);
            });
        }

        // Get tickets with pagination
        $tickets = $query->latest()->paginate(10)->withQueryString();

        // Get stats
        $stats = [
            'open' => Report::open()->count(),
            'assigned' => Report::where('status', 'assigned')->count(),
            'in_progress' => Report::where('status', 'in-progress')->count(),
            'high_priority' => Report::where('priority', 'high')->open()->count(),
        ];

        // Get IT staff for assignment dropdown
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();

        // Get unique labs for filter
        $labs = Lab::where('is_active', true)->pluck('name');

        return view('it.tickets.index', compact('tickets', 'stats', 'itStaff', 'labs'));
    }

    // Show single ticket detail (VIEW ONLY)
    public function show($id)
    {
        $ticket = Report::with(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user'])->findOrFail($id);

        return view('it.tickets.show', compact('ticket'));
    }

    // Show edit form for ticket (EDIT ONLY)
    public function edit($id)
    {
        $ticket = Report::with(['reporter', 'assignedTo', 'equipment.lab'])->findOrFail($id);
        
        // Get IT staff for assignment dropdown
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();

        return view('it.tickets.edit', compact('ticket', 'itStaff'));
    }

    // Update ticket (status, priority, assignment)
    public function update(Request $request, $id)
    {
        $ticket = Report::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:new,assigned,in-progress,resolved,closed'],
            'priority' => ['required', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        // Track what changed
        $statusChanged = $ticket->status !== $validated['status'];
        $priorityChanged = $ticket->priority !== $validated['priority'];
        $assignmentChanged = $ticket->assigned_to !== $validated['assigned_to'];

        // Store old values
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;

        // Update the ticket
        $ticket->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'],
        ]);

        // LOG CHANGES
        if ($statusChanged) {
            $this->logStatusChange($ticket, $oldStatus, $validated['status']);
        }
        if ($priorityChanged) {
            $this->logPriorityChange($ticket, $oldPriority, $validated['priority']);
        }
        if ($assignmentChanged && $validated['assigned_to']) {
            $this->logAssignment($ticket, $validated['assigned_to']);
        }

        return redirect()
            ->route('it.tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully!');
    }

    // Assign ticket to self
    public function assignToSelf($id)
    {
        $ticket = Report::findOrFail($id);

        $ticket->update([
            'assigned_to' => Auth::id(),
            'status' => 'assigned',
        ]);
        
        // Log assignment
        $this->logAssignment($ticket, Auth::id());
        
        // Log status change if needed
        if ($ticket->getOriginal('status') !== 'assigned') {
            $this->logStatusChange($ticket, $ticket->getOriginal('status'), 'assigned');
        }

        return redirect()
            ->route('it.tickets.show', $ticket->id)
            ->with('success', 'Ticket assigned to you!');
    }

    // Show IT assignments (tickets assigned to current user)
    public function assignments(Request $request)
    {
        $query = Report::where('assigned_to', Auth::id())
            ->with(['reporter', 'equipment.lab']);

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->open();
            } else {
                $query->where('status', $request->status);
            }
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        // Stats
        $stats = [
            'total_assigned' => Report::where('assigned_to', Auth::id())->count(),
            'in_progress' => Report::where('assigned_to', Auth::id())->where('status', 'in-progress')->count(),
            'high_priority' => Report::where('assigned_to', Auth::id())->where('priority', 'high')->open()->count(),
            'completed_today' => Report::where('assigned_to', Auth::id())
                ->where('status', 'resolved')
                ->whereHas('transactions', function($query) {
                    $query->where('action', 'status_changed')
                          ->where('new_value', 'resolved')
                          ->whereDate('created_at', today());
                })
                ->count(),
        ];

        return view('it.assignments.index', compact('tickets', 'stats'));
    }
}