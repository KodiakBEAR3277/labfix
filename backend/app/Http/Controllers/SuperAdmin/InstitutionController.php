<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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
                'id'            => $institution->id,
                'name'          => $institution->name,
                'slug'          => $institution->slug,
                'contact_email' => $institution->contact_email,
                'address'       => $institution->address,
                'is_active'     => $institution->is_active,
                'plan'          => $institution->plan ?: 'free',
                'user_count'    => $institution->users_count,
                'lab_count'     => $institution->labs_count,
                'created_at'    => $institution->created_at->diffForHumans(),
            ]);

        return Inertia::render('SuperAdmin/Institutions/Index', compact('institutions'));
    }

    // Create a live institution with its first admin account. Mirrors
    // InstitutionRequest::approve(): the institution, its default settings,
    // and the admin are created together, so a new tenant is usable straight
    // away and is never left without someone who can sign in.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'contact_email'         => ['nullable', 'string', 'email', 'max:255'],
            'address'               => ['nullable', 'string', 'max:255'],
            'plan'                  => ['required', 'in:free,standard,pro,enterprise'],
            'is_active'             => ['boolean'],

            'admin_first_name'      => ['required', 'string', 'max:255'],
            'admin_last_name'       => ['required', 'string', 'max:255'],
            'admin_email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password'        => ['required', 'confirmed', Password::defaults()],
        ]);

        $institution = DB::transaction(function () use ($validated, $request) {
            $institution = Institution::create([
                'name'          => $validated['name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'address'       => $validated['address'] ?? null,
                'plan'          => $validated['plan'],
                'is_active'     => $request->boolean('is_active', true),
            ]);

            Setting::seedDefaultsFor($institution->id);

            User::create([
                'institution_id' => $institution->id,
                'first_name'     => $validated['admin_first_name'],
                'last_name'      => $validated['admin_last_name'],
                'email'          => $validated['admin_email'],
                'role'           => 'admin',
                'password'       => Hash::make($validated['admin_password']),
            ]);

            return $institution;
        });

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$institution->name} created with its admin account.");
    }

    // Update an institution's profile, plan and active flag.
    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'address'       => ['nullable', 'string', 'max:255'],
            'plan'          => ['required', 'in:free,standard,pro,enterprise'],
            'is_active'     => ['boolean'],
        ]);

        $institution->update([
            'name'          => $validated['name'],
            'contact_email' => $validated['contact_email'] ?? null,
            'address'       => $validated['address'] ?? null,
            'plan'          => $validated['plan'],
            'is_active'     => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$institution->name} updated successfully.");
    }

    // Delete an institution only when it is genuinely empty. Users and reports
    // use restrictOnDelete at the database level, and silently cascading a
    // tenant's labs/equipment would be too destructive — deactivation is the
    // intended path for a live institution.
    public function destroy(Institution $institution)
    {
        $name = $institution->name;

        if (
            $institution->users()->count() > 0 ||
            $institution->labs()->count() > 0 ||
            $institution->equipment()->count() > 0
        ) {
            return redirect()
                ->route('superadmin.institutions.index')
                ->with('error', "Cannot delete {$name} while it still has users, labs or equipment. Deactivate it instead.");
        }

        $institution->delete();

        return redirect()
            ->route('superadmin.institutions.index')
            ->with('success', "{$name} deleted.");
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