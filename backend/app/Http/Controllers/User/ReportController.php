<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Traits\LogsTicketTransactions;
use Inertia\Inertia;
use App\Models\Lab;
use App\Models\Equipment;

class ReportController extends Controller
{
    use LogsTicketTransactions;

    public function index(Request $request)
    {
        $query = Report::where('user_id', Auth::id())->with('assignedTo', 'equipment.lab');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active')        $query->open();
            elseif ($request->status === 'resolved')  $query->closed();
            else                                       $query->status($request->status);
        }

        $reports = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total'               => Report::where('user_id', Auth::id())->count(),
            'active'              => Report::where('user_id', Auth::id())->open()->count(),
            'resolved'            => Report::where('user_id', Auth::id())->closed()->count(),
            'avg_resolution_time' => '2.5',
        ];

        return Inertia::render('User/Reports/Index', [
            'reports' => $reports,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show($id)
    {
        $report = Report::where('user_id', Auth::id())
            ->with(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user'])
            ->findOrFail($id);

        $report->created_at_human = $report->created_at->diffForHumans();
        $report->assigned_to_user = $report->assignedTo;

        return Inertia::render('User/Reports/Show', compact('report'));
    }

    public function create()
    {
        $labs = Lab::where('is_active', true)
            ->get()
            ->map(fn ($lab) => [
                'id'                => $lab->id,
                'name'              => $lab->name,
                'capacity'          => $lab->capacity,
                'operational_count' => $lab->operational_count,
            ]);

        return Inertia::render('User/Reports/Create', [
            'labs' => $labs,
            'maintenance' => [
                'active'  => (bool) Setting::get('maintenance_mode', false),
                'message' => Setting::get('maintenance_message', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_id'         => ['required', 'exists:labs,id'],
            'equipment_id'   => ['nullable', 'exists:equipment,id'],
            'category'       => ['required', 'in:hardware,software,network,other'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'min:10'],
            // useForm sends files under 'attachments' (array)
            'attachments'    => ['nullable', 'array'],
            'attachments.*'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Resolve general equipment if none selected
        if (empty($validated['equipment_id'])) {
            $lab = Lab::findOrFail($validated['lab_id']);
            $generalEquipment = Equipment::firstOrCreate(
                ['lab_id' => $lab->id, 'equipment_code' => 'GENERAL'],
                ['type' => 'other', 'status' => 'operational', 'notes' => 'General lab issues without specific equipment']
            );
            $validated['equipment_id'] = $generalEquipment->id;
        }

        // Handle file uploads — field name matches useForm: 'attachments'
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('attachments', 'public');
            }
        }

        $report = Report::create([
            'ticket_number' => Report::generateTicketNumber(),
            'user_id'       => Auth::id(),
            'equipment_id'  => $validated['equipment_id'],
            'category'      => $validated['category'],
            'title'         => $validated['title'],
            'description'   => $validated['description'],
            'attachments'   => $attachmentPaths,
            'status'        => 'new',
            'priority'      => $this->determinePriority($validated),
        ]);

        $this->logCreated($report);
        $report->equipment->updateStatusFromReports();

        return redirect()
            ->route('user.reports.show', $report->id)
            ->with('success', "Report submitted successfully! Ticket: {$report->ticket_number}");
    }

    public function edit($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);

        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot edit ticket that has been assigned to a technician.');
        }

        return Inertia::render('User/Reports/Edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);

        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot edit ticket that has been assigned to a technician.');
        }

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['required', 'string', 'min:10'],
            'category'            => ['required', 'in:hardware,software,network,other'],
            // useForm sends new files under 'new_attachments'
            'new_attachments'     => ['nullable', 'array'],
            'new_attachments.*'   => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Append new files to existing attachment paths
        $attachmentPaths = $report->attachments ?? [];
        if ($request->hasFile('new_attachments')) {
            foreach ($request->file('new_attachments') as $file) {
                $attachmentPaths[] = $file->store('attachments', 'public');
            }
        }

        $report->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'category'    => $validated['category'],
            'attachments' => $attachmentPaths,
        ]);

        $this->logUpdate($report);

        return redirect()
            ->route('user.reports.show', $report->id)
            ->with('success', 'Report updated successfully!');
    }

    public function destroy($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);

        if ($report->assigned_to) {
            return redirect()
                ->route('user.reports.show', $report->id)
                ->with('error', 'Cannot delete ticket that has been assigned to a technician.');
        }

        $ticketNumber = $report->ticket_number;
        $this->logDeletion($report);
        $report->delete();

        return redirect()
            ->route('user.reports.index')
            ->with('success', "Ticket {$ticketNumber} has been cancelled. Admins can restore it if needed.");
    }

    private function determinePriority(array $data): string
    {
        $keywords = ['not working', 'broken', 'urgent', 'critical', 'cannot', "can't", 'multiple', 'all'];
        $text = strtolower($data['title'] . ' ' . $data['description']);

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) return 'high';
        }

        return Setting::get('default_priority', 'medium');
    }
}