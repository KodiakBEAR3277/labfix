<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Illuminate\Http\Request;

class LabController extends Controller
{
    // Show all labs
    public function index()
    {
        $labs = Lab::with('equipment')->get();
        
        $stats = [
            'total_labs' => $labs->count(),
            'total_equipment' => $labs->sum(fn($lab) => $lab->equipment->count()),
            'total_operational' => $labs->sum(fn($lab) => $lab->operational_count),
            'total_maintenance' => $labs->sum(fn($lab) => $lab->maintenance_count),
        ];

        return view('admin.labs.index', compact('labs', 'stats'));
    }

    // Show create form
    public function create()
    {
        return view('admin.labs.create');
    }

    // Store new lab
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:labs,code'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = true;

        Lab::create($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab created successfully!');
    }

    // Show edit form
    public function edit($id)
    {
        $lab = Lab::findOrFail($id);
        return response()->json($lab);
    }

    // Update lab
    public function update(Request $request, $id)
    {
        $lab = Lab::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:labs,code,' . $id],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $lab->update($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab updated successfully!');
    }

    // Toggle lab status
    public function toggleStatus($id)
    {
        $lab = Lab::findOrFail($id);
        $lab->update(['is_active' => !$lab->is_active]);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Lab status updated successfully!');
    }

    // Delete lab
    public function destroy($id)
    {
        $lab = Lab::findOrFail($id);
        
        // Check if lab has equipment
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