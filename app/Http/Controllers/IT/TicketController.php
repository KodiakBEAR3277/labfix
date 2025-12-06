<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
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
        $ticket = Report::with(['reporter', 'assignedTo', 'equipment.lab'])->findOrFail($id);

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

        // Track if status changed to resolved
        $wasResolved = $ticket->status !== 'resolved' && $validated['status'] === 'resolved';

        // Update ticket
        $ticket->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'],
            'assigned_at' => $validated['assigned_to'] && !$ticket->assigned_at ? now() : $ticket->assigned_at,
            'resolved_at' => $wasResolved ? now() : $ticket->resolved_at,
        ]);

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
            'assigned_at' => $ticket->assigned_at ?? now(),
        ]);

        return redirect()
            ->route('it.tickets.show', $ticket->id)
            ->with('success', 'Ticket assigned to you!');
    }

    // Bulk operations view
    public function bulk(Request $request)
    {
        $selectedIds = $request->query('ids', []);
        
        if (is_string($selectedIds)) {
            $selectedIds = explode(',', $selectedIds);
        }

        $selectedTickets = Report::with(['equipment.lab'])->whereIn('id', $selectedIds)->get();

        // Get IT staff for assignment
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();

        return view('it.tickets.bulk', compact('selectedTickets', 'itStaff'));
    }

    // Process bulk operations
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array'],
            'ticket_ids.*' => ['exists:reports,id'],
            'action' => ['required', 'in:assign,status,priority,close'],
            'assigned_to' => ['required_if:action,assign', 'nullable', 'exists:users,id'],
            'status' => ['required_if:action,status', 'nullable', 'in:new,assigned,in-progress,resolved,closed'],
            'priority' => ['required_if:action,priority', 'nullable', 'in:low,medium,high'],
        ]);

        $tickets = Report::whereIn('id', $validated['ticket_ids']);

        switch ($validated['action']) {
            case 'assign':
                $tickets->update([
                    'assigned_to' => $validated['assigned_to'],
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);
                $message = 'Tickets assigned successfully!';
                break;

            case 'status':
                $updateData = ['status' => $validated['status']];
                if ($validated['status'] === 'resolved') {
                    $updateData['resolved_at'] = now();
                }
                $tickets->update($updateData);
                $message = 'Ticket status updated successfully!';
                break;

            case 'priority':
                $tickets->update(['priority' => $validated['priority']]);
                $message = 'Ticket priority updated successfully!';
                break;

            case 'close':
                $tickets->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                $message = 'Tickets closed successfully!';
                break;

            default:
                $message = 'Operation completed!';
        }

        return redirect()
            ->route('it.tickets.index')
            ->with('success', $message);
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
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        return view('it.assignments', compact('tickets', 'stats'));
    }
}