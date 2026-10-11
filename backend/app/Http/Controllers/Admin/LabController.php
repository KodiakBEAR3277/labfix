<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LabController extends Controller
{
    // Show all labs — the edit() and equipment edit() still return JSON for modals,
    // so everything (labs + equipment CRUD) lives on this single Inertia page.
    public function index()
    {
        $labs = Lab::with('equipment')->get();

        $stats = [
            'total_labs'        => $labs->count(),
            'total_equipment'   => $labs->sum(fn($lab) => $lab->equipment->count()),
            'total_operational' => $labs->sum(fn($lab) => $lab->operational_count),
            'total_maintenance' => $labs->sum(fn($lab) => $lab->maintenance_count),
        ];

        // Reuses the counts already computed above — no extra queries.
        // null max means unlimited, mirrored as-is for the Vue page to check.
        $institution = Auth::user()->institution;
        $planLimits = [
            'plan'      => $institution->planLabel(),
            'labs'      => ['current' => $stats['total_labs'], 'max' => $institution->labLimit()],
            'equipment' => ['current' => $stats['total_equipment'], 'max' => $institution->equipmentLimit()],
        ];

        return Inertia::render('Admin/Labs/Index', compact('labs', 'stats', 'planLimits'));
    }

    // create() is unused — lab creation is handled via modal on the index page.
    // Kept for resource completeness; redirects to index.
    public function create()
    {
        return redirect()->route('admin.labs.index');
    }

    // Store new lab — code uniqueness scoped per institution, plus a hard
    // cap on how many labs the institution's plan allows.
    public function store(Request $request)
    {
        if (!Auth::user()->institution->canAddLab()) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', "You've reached your plan's lab limit. Upgrade to add more labs.");
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => [
                'required', 'string', 'max:50',
                Rule::unique('labs')->where('institution_id', Auth::user()->institution_id),
            ],
            'location'    => ['nullable', 'string', 'max:255'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = true;
        Lab::create($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab created successfully!');
    }

    // Returns JSON for the edit modal — unchanged
    public function edit($id)
    {
        $lab = Lab::findOrFail($id);
        return response()->json($lab);
    }

    // Update lab — business logic untouched, code uniqueness now scoped per institution
    public function update(Request $request, $id)
    {
        $lab = Lab::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => [
                'required', 'string', 'max:50',
                Rule::unique('labs')->where('institution_id', Auth::user()->institution_id)->ignore($id),
            ],
            'location'    => ['nullable', 'string', 'max:255'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $lab->update($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab updated successfully!');
    }

    // Toggle lab active status — business logic untouched
    public function toggleStatus($id)
    {
        $lab = Lab::findOrFail($id);
        $lab->update(['is_active' => !$lab->is_active]);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab status updated successfully!');
    }

    // Delete lab — business logic untouched
    public function destroy($id)
    {
        $lab = Lab::findOrFail($id);

        if ($lab->equipment()->count() > 0) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Cannot delete lab with existing equipment. Remove all equipment first.');
        }

        $labName = $lab->name;
        $lab->delete();

        return redirect()
            ->route('admin.labs.index')
            ->with('success', "Lab '{$labName}' has been deleted successfully!");
    }
}