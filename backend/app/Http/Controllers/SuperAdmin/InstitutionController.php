<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    // List every institution on the platform, with basic counts —
    // never a merged view of everyone's tickets/labs/users.
    public function index()
    {
        $institutions = Institution::withCount(['users', 'labs'])
            ->orderBy('name')
            ->get()
            ->map(fn ($institution) => [
                'id'         => $institution->id,
                'name'       => $institution->name,
                'slug'       => $institution->slug,
                'is_active'  => $institution->is_active,
                'plan'       => $institution->plan ?: 'free',
                'user_count' => $institution->users_count,
                'lab_count'  => $institution->labs_count,
                'created_at' => $institution->created_at->diffForHumans(),
            ]);

        return Inertia::render('SuperAdmin/Institutions/Index', compact('institutions'));
    }

    // Change an institution's plan. This is how an upgrade request ("contact
    // us") actually gets fulfilled. Downgrading never deletes anything — it
    // only stops the institution adding more once it's over the new limit.
    public function updatePlan(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:free,standard,pro,enterprise'],
        ]);

        $institution->update(['plan' => $validated['plan']]);

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$institution->name} is now on the " . ucfirst($validated['plan']) . " plan.");
    }

    // Activate or deactivate an institution platform-wide
    public function toggleStatus(Institution $institution)
    {
        $institution->update(['is_active' => !$institution->is_active]);

        $status = $institution->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$institution->name} has been {$status}.");
    }
}