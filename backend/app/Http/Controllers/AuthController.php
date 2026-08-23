<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController extends Controller
{
    // Show login page — now served through Inertia/inertia.blade.php
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    // Handle login POST — unchanged, session redirect still works with Inertia
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Show register page — now served through Inertia/inertia.blade.php
    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    // Handle register POST — branches on institution mode:
    //   'join'   → pick an existing, active institution, becomes a student there
    //   'create' → registers a brand-new institution, becomes its first admin
    public function register(Request $request)
    {
        $validated = $request->validate([
            'mode'                      => ['required', 'in:join,create'],
            'institution_id'            => ['required_if:mode,join', 'nullable', 'exists:institutions,id'],
            'institution_name'          => ['required_if:mode,create', 'nullable', 'string', 'max:255'],
            'institution_contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'first_name'                => ['required', 'string', 'max:255'],
            'last_name'                 => ['required', 'string', 'max:255'],
            'email'                     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'                  => ['required', 'confirmed', Password::defaults()],
            'terms'                     => ['accepted'],
        ]);

        if ($validated['mode'] === 'join') {
            $institution = Institution::findOrFail($validated['institution_id']);

            if (!$institution->is_active) {
                return back()
                    ->withErrors(['institution_id' => 'This institution is not currently accepting new accounts.'])
                    ->onlyInput('email', 'first_name', 'last_name');
            }

            $user = $this->createUser($validated, $institution->id, 'student');
        } else {
            // Institution, its default settings, and its first admin are all
            // created together — if any step fails, none should be left
            // half-created.
            $user = DB::transaction(function () use ($validated) {
                $institution = Institution::create([
                    'name'          => $validated['institution_name'],
                    'contact_email' => $validated['institution_contact_email'] ?: $validated['email'],
                ]);

                Setting::seedDefaultsFor($institution->id);

                return $this->createUser($validated, $institution->id, 'admin');
            });
        }

        Auth::login($user);

        return $this->redirectBasedOnRole($user);
    }

    private function createUser(array $validated, int $institutionId, string $role): User
    {
        return User::create([
            'institution_id' => $institutionId,
            'first_name'     => $validated['first_name'],
            'last_name'      => $validated['last_name'],
            'email'          => $validated['email'],
            'role'           => $role,
            'password'       => Hash::make($validated['password']),
        ]);
    }

    // Handle logout POST — unchanged
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function redirectBasedOnRole(User $user)
    {
        return match($user->role) {
            'admin'      => redirect()->route('admin.dashboard'),
            'it-support' => redirect()->route('it.dashboard'),
            default      => redirect()->route('user.dashboard'),
        };
    }
}