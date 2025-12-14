<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsTicketTransactions;

class TicketController extends Controller
{
    use LogsTicketTransactions;
    // Show all tickets with admin controls
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

        // Assigned filter
        if ($request->filled('assigned') && $request->assigned !== 'all') {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned);
            }
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => Report::count(),
            'open' => Report::open()->count(),
            'assigned' => Report::where('status', 'assigned')->count(),
            'in_progress' => Report::where('status', 'in-progress')->count(),
            'resolved' => Report::closed()->count(),
            'high_priority' => Report::where('priority', 'high')->open()->count(),
            'unassigned' => Report::whereNull('assigned_to')->open()->count(),
        ];

        // Get IT staff for filters
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();
        
        // Get unique labs for filter
        $labs = Lab::where('is_active', true)->pluck('name');

        return view('admin.tickets.index', compact('tickets', 'stats', 'itStaff', 'labs'));
    }

    // Show ticket details
    public function show($id)
    {
        $ticket = Report::with(['reporter', 'assignedTo', 'equipment.lab'])->findOrFail($id);
        return view('admin.tickets.show', compact('ticket'));
    }

    // Show edit form
    public function edit($id)
    {
        $ticket = Report::with(['reporter', 'assignedTo', 'equipment.lab'])->findOrFail($id);
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();
        
        return view('admin.tickets.edit', compact('ticket', 'itStaff'));
    }

    // Update ticket
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
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully!');
    }

    // Bulk operations
    public function bulk(Request $request)
    {
        $selectedIds = $request->query('ids', []);
        
        if (is_string($selectedIds)) {
            $selectedIds = explode(',', $selectedIds);
        }

        $selectedTickets = Report::with(['equipment.lab'])->whereIn('id', $selectedIds)->get();
        $itStaff = User::whereIn('role', ['it-support', 'admin'])->get();

        return view('admin.tickets.bulk', compact('selectedTickets', 'itStaff'));
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

        $tickets = Report::whereIn('id', $validated['ticket_ids'])->get();
        $updatedCount = 0;

        foreach ($tickets as $ticket) {
            switch ($validated['action']) {
                case 'assign':
                    $oldAssignedTo = $ticket->assigned_to;
                    
                    $ticket->update([
                        'assigned_to' => $validated['assigned_to'],
                        'status' => 'assigned',
                    ]);
                    
                    // Log assignment
                    $assignedUser = User::find($validated['assigned_to']);
                    $this->logTransaction(
                        $ticket,
                        'assigned',
                        'Ticket assigned to ' . $assignedUser->full_name,
                        $oldAssignedTo,
                        $validated['assigned_to']
                    );
                    
                    // Log status change if it was changed
                    if ($ticket->status !== 'assigned') {
                        $this->logTransaction(
                            $ticket,
                            'status_changed',
                            'Status changed to Assigned',
                            $ticket->status,
                            'assigned'
                        );
                    }
                    
                    $message = 'Tickets assigned successfully!';
                    break;

                case 'status':
                    $oldStatus = $ticket->status;
                    $updateData = ['status' => $validated['status']];
                    
                    $ticket->update($updateData);
                    
                    // Log status change
                    $this->logTransaction(
                        $ticket,
                        'status_changed',
                        'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                        $oldStatus,
                        $validated['status']
                    );
                    
                    $message = 'Ticket status updated successfully!';
                    break;

                case 'priority':
                    $oldPriority = $ticket->priority;
                    
                    $ticket->update(['priority' => $validated['priority']]);
                    
                    // Log priority change
                    $this->logTransaction(
                        $ticket,
                        'priority_changed',
                        'Priority changed from ' . ucfirst($oldPriority) . ' to ' . ucfirst($validated['priority']),
                        $oldPriority,
                        $validated['priority']
                    );
                    
                    $message = 'Ticket priority updated successfully!';
                    break;

                case 'close':
                    $oldStatus = $ticket->status;
                    
                    $ticket->update([
                        'status' => 'closed',
                    ]);
                    
                    // Log status change to closed
                    $this->logTransaction(
                        $ticket,
                        'status_changed',
                        'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to Closed',
                        $oldStatus,
                        'closed'
                    );
                    
                    $message = 'Tickets closed successfully!';
                    break;

                default:
                    $message = 'Operation completed!';
            }
            
            $updatedCount++;
        }

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', $message . " ({$updatedCount} tickets updated)");
    }

    // Delete ticket (admin only)
    public function destroy($id)
    {
        $ticket = Report::findOrFail($id);
        
        // Store ticket number for success message
        $ticketNumber = $ticket->ticket_number;
        
        // Delete the ticket
        $ticket->delete();

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', "Ticket {$ticketNumber} has been deleted successfully!");
    }

    // View trashed tickets
    public function trashed()
    {
        $tickets = Report::onlyTrashed()
            ->with(['reporter', 'equipment.lab'])
            ->latest('deleted_at')
            ->paginate(20);
        
        return view('admin.tickets.trashed', compact('tickets'));
    }

    // Restore a ticket
    public function restore($id)
    {
        $ticket = Report::onlyTrashed()->findOrFail($id);
        $ticket->restore();
        
        return redirect()
            ->route('admin.tickets.index')
            ->with('success', "Ticket {$ticket->ticket_number} has been restored!");
    }

    // Permanently delete
    public function forceDelete($id)
    {
        $ticket = Report::onlyTrashed()->findOrFail($id);
        $ticketNumber = $ticket->ticket_number;
        
        // Delete attachments
        if ($ticket->attachments) {
            foreach ($ticket->attachments as $attachment) {
                Storage::disk('public')->delete($attachment);
            }
        }
        
        $ticket->forceDelete();
        
        return redirect()
            ->route('admin.tickets.trashed')
            ->with('success', "Ticket {$ticketNumber} has been permanently deleted!");
    }
}