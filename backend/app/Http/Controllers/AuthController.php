<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    // Handle register POST — unchanged
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'   => ['required', 'confirmed', Password::defaults()],
            'terms'      => ['accepted'],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'role'       => 'student',
            'password'   => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return $this->redirectBasedOnRole($user);
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