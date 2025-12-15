<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    // Show all assignments for current IT user
    public function index(Request $request)
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

        // Stats - updated to use transactions
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

    // Show single assignment detail (VIEW ONLY)
    public function show($id)
    {
        $ticket = Report::where('assigned_to', Auth::id())
            ->with(['reporter', 'equipment.lab', 'transactions.user'])
            ->findOrFail($id);

        return view('it.assignments.show', compact('ticket'));
    }

    // Show edit form for assignment (UPDATE STATUS ONLY)
    public function edit($id)
    {
        $ticket = Report::where('assigned_to', Auth::id())
            ->with(['reporter', 'equipment.lab'])
            ->findOrFail($id);

        return view('it.assignments.edit', compact('ticket'));
    }

    // Update assignment (STATUS/PRIORITY ONLY - No reassignment)
    public function update(Request $request, $id)
    {
        $ticket = Report::where('assigned_to', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:assigned,in-progress,resolved'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        // Track if status or priority changed
        $statusChanged = $ticket->status !== $validated['status'];
        $priorityChanged = $ticket->priority !== $validated['priority'];
        
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;

        // Update ticket
        $ticket->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
        ]);

        // Log changes
        if ($statusChanged) {
            \App\Models\TicketTransaction::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'old_value' => $oldStatus,
                'new_value' => $validated['status'],
                'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                'created_at' => now(),
            ]);
        }
        
        if ($priorityChanged) {
            \App\Models\TicketTransaction::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'priority_changed',
                'old_value' => $oldPriority,
                'new_value' => $validated['priority'],
                'description' => 'Priority changed from ' . ucfirst($oldPriority) . ' to ' . ucfirst($validated['priority']),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('it.assignments.show', $ticket->id)
            ->with('success', 'Ticket updated successfully! User has been notified.');
    }
}