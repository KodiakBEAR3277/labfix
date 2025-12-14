<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use App\Traits\LogsTicketTransactions;

class ReportController extends Controller
{
    use LogsTicketTransactions;
    
    // Show all user's reports
    public function index(Request $request)
    {
        $query = Report::where('user_id', Auth::id())->with('assignedTo');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('lab_location', 'like', "%{$search}%")
                  ->orWhere('equipment_id', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->open();
            } elseif ($request->status === 'resolved') {
                $query->closed();
            } else {
                $query->status($request->status);
            }
        }

        // Get reports with pagination
        $reports = $query->latest()->paginate(10)->withQueryString();

        // Get stats
        $stats = [
            'total' => Report::where('user_id', Auth::id())->count(),
            'active' => Report::where('user_id', Auth::id())->open()->count(),
            'resolved' => Report::where('user_id', Auth::id())->closed()->count(),
            'avg_resolution_time' => '2.5', // TODO: Calculate actual average
        ];

        return view('user.reports.index', compact('reports', 'stats'));
    }

    // Show single report
    public function show($id)
    {
        $report = Report::where('user_id', Auth::id())
            ->with(['reporter', 'assignedTo'])
            ->findOrFail($id);

        return view('user.reports.show', compact('report'));
    }

    // Show create form
    public function create()
    {
        return view('user.reports.create');
    }

    // Store new report
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_id' => ['required', 'exists:labs,id'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'category' => ['required', 'in:hardware,software,network,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // If no equipment selected, create a general equipment entry or handle differently
        if (!$validated['equipment_id']) {
            // Get lab
            $lab = \App\Models\Lab::findOrFail($validated['lab_id']);
            
            // Create a temporary "General" equipment for this lab if it doesn't exist
            $generalEquipment = \App\Models\Equipment::firstOrCreate([
                'lab_id' => $lab->id,
                'equipment_code' => 'GENERAL',
            ], [
                'type' => 'other',
                'status' => 'operational',
                'notes' => 'General lab issues without specific equipment',
            ]);
            
            $validated['equipment_id'] = $generalEquipment->id;
        }

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        // Create report
        $report = Report::create([
            'ticket_number' => Report::generateTicketNumber(),
            'user_id' => Auth::id(),
            'equipment_id' => $validated['equipment_id'],
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'attachments' => $attachmentPaths,
            'status' => 'new',
            // Use system setting for default priority, with keyword detection override
            'priority' => $this->determinePriority($validated),
        ]);

        $this->logCreated($report);

        // Update equipment status
        $report->equipment->updateStatusFromReports();

        return redirect()
            ->route('user.reports.show', $report->id)
            ->with('success', "Report submitted successfully! Ticket: {$report->ticket_number}");
    }

    // Show edit form
    public function edit($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);
        
        // Only allow editing if ticket hasn't been assigned yet
        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot edit ticket that has been assigned to a technician.');
        }
        
        return view('user.reports.edit', compact('report'));
    }

    // Update report
    public function update(Request $request, $id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);
        
        // Only allow updating if ticket hasn't been assigned yet
        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot edit ticket that has been assigned to a technician.');
        }
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'category' => ['required', 'in:hardware,software,network,other'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Handle new file uploads
        $attachmentPaths = $report->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $report->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'attachments' => $attachmentPaths,
        ]);

        $this->logUpdate($report);

        return redirect()
            ->route('user.reports.show', $report->id)
            ->with('success', 'Report updated successfully!');
    }

    // Soft delete report
    public function destroy($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);
        
        // Only allow deletion if ticket hasn't been assigned yet
        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot delete ticket that has been assigned to a technician. Please contact support if you need to cancel this ticket.');
        }
        
        // Soft delete
        $ticketNumber = $report->ticket_number;
        $this->logDeletion($report);
        $report->delete();

        return redirect()
            ->route('user.reports.index')
            ->with('success', "Ticket {$ticketNumber} has been cancelled. Admins can restore it if needed.");
    }

    // Auto-determine priority based on keywords
    private function determinePriority(array $data): string
    {
        $highPriorityKeywords = ['not working', 'broken', 'urgent', 'critical', 'cannot', "can't", 'multiple', 'all'];
        $text = strtolower($data['title'] . ' ' . $data['description']);

        foreach ($highPriorityKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'high';
            }
        }

        return Setting::get('default_priority', 'medium');
    }
}