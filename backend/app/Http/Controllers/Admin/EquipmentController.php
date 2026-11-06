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
        if (!auth()->user()->institution->canAddEquipment()) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', "You've reached your plan's equipment limit. Upgrade to add more.");
        }

        $validated = $request->validate([
            'lab_id' => ['required', 'exists:labs,id'],
            'equipment_code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:computer,printer,projector,other'],
            'status' => ['required', 'in:operational,has-issue,maintenance,retired'],
            'notes' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date', 'after_or_equal:purchase_date'],
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

    // Bulk-create equipment for one lab. Codes arrive as a newline/comma
    // separated string; duplicates already in the lab are skipped.
    public function bulkStore(Request $request, $labId)
    {
        $lab = Lab::findOrFail($labId);
        $institution = $request->user()->institution;

        $validated = $request->validate([
            'codes'  => ['required', 'string', 'max:20000'],
            'type'   => ['required', 'in:computer,printer,projector,other'],
            'status' => ['required', 'in:operational,has-issue,maintenance,retired'],
        ]);

        $codes = collect(preg_split('/[\r\n,]+/', $validated['codes']))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->unique(fn ($code) => mb_strtolower($code))
            ->values();

        if ($codes->isEmpty()) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Enter at least one equipment code.');
        }

        if ($codes->contains(fn ($code) => mb_strlen($code) > 50)) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'Equipment codes must be 50 characters or fewer.');
        }

        $existing = Equipment::where('lab_id', $lab->id)
            ->whereIn('equipment_code', $codes->all())
            ->pluck('equipment_code')
            ->map(fn ($code) => mb_strtolower($code))
            ->all();

        $newCodes = $codes
            ->reject(fn ($code) => in_array(mb_strtolower($code), $existing, true))
            ->values();

        if ($newCodes->isEmpty()) {
            return redirect()
                ->route('admin.labs.index')
                ->with('error', 'All of those equipment codes already exist in this lab.');
        }

        // Enforce the plan limit against what will actually be created.
        $limit = $institution->equipmentLimit();

        if ($limit !== null) {
            $current = $institution->equipment()->count();

            if ($current + $newCodes->count() > $limit) {
                $remaining = max(0, $limit - $current);

                return redirect()
                    ->route('admin.labs.index')
                    ->with(
                        'error',
                        "This would exceed your plan's equipment limit. You can add {$remaining} more."
                    );
            }
        }

        foreach ($newCodes as $code) {
            Equipment::create([
                'lab_id'         => $lab->id,
                'equipment_code' => $code,
                'type'           => $validated['type'],
                'status'         => $validated['status'],
            ]);
        }

        $skipped = $codes->count() - $newCodes->count();
        $message = $newCodes->count() . ' equipment added';

        if ($skipped > 0) {
            $message .= ', ' . $skipped . ' duplicate' . ($skipped === 1 ? '' : 's') . ' skipped';
        }

        return redirect()->route('admin.labs.index')->with('success', $message . '.');
    }

    // Past tickets for one device — consumed by the admin lab page history panel.
    public function history($id)
    {
        $equipment = Equipment::with('lab')->findOrFail($id);

        return response()->json([
            'equipment' => $equipment,
            'lab'       => $equipment->lab,
            'reports'   => $equipment->reports()
                ->latest()
                ->take(50)
                ->get(['id', 'ticket_number', 'title', 'status', 'priority', 'created_at', 'updated_at']),
            'counts' => [
                'open'  => $equipment->reports()->open()->count(),
                'total' => $equipment->reports()->count(),
            ],
        ]);
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
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date', 'after_or_equal:purchase_date'],
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