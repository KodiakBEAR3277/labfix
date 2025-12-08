<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    // Store new equipment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_id' => ['required', 'exists:labs,id'],
            'equipment_code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:computer,printer,projector,other'],
            'status' => ['required', 'in:operational,has-issue,maintenance,retired'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check if equipment code already exists in this lab
        $exists = Equipment::where('lab_id', $validated['lab_id'])
            ->where('equipment_code', $validated['equipment_code'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Equipment code already exists in this lab!');
        }

        Equipment::create($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Equipment added successfully!');
    }

    // Show edit form (returns JSON for modal)
    public function edit($id)
    {
        $equipment = Equipment::findOrFail($id);
        return response()->json($equipment);
    }

    // Update equipment
    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'lab_id' => ['required', 'exists:labs,id'],
            'equipment_code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:computer,printer,projector,other'],
            'status' => ['required', 'in:operational,has-issue,maintenance,retired'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check if equipment code already exists in this lab (excluding current equipment)
        $exists = Equipment::where('lab_id', $validated['lab_id'])
            ->where('equipment_code', $validated['equipment_code'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Equipment code already exists in this lab!');
        }

        $equipment->update($validated);

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Equipment updated successfully!');
    }

    // Delete equipment
    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        
        // Check if equipment has open reports
        if ($equipment->activeReports()->count() > 0) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Cannot delete equipment with open reports. Resolve all reports first.');
        }

        $equipmentCode = $equipment->equipment_code;
        $equipment->delete();

        return redirect()
            ->route('admin.labs.index')
            ->with('success', "Equipment '{$equipmentCode}' has been deleted successfully!");
    }
}