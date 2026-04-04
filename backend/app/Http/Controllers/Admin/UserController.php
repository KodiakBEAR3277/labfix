<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    // Show all users
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_staff_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total'      => User::count(),
            'students'   => User::where('role', 'student')->count(),
            'staff'      => User::where('role', 'staff')->count(),
            'it_support' => User::where('role', 'it-support')->count(),
            'admins'     => User::where('role', 'admin')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    // Show single user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return Inertia::render('Admin/Users/Show', compact('user'));
    }

    // Show create form
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    // Store new user — business logic untouched
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role'            => ['required', 'in:student,staff,it-support,admin'],
            'password'        => ['required', Password::defaults()],
            'phone'           => ['nullable', 'string', 'max:20'],
            'student_staff_id'=> ['nullable', 'string', 'max:50'],
        ]);

        User::create([
            'first_name'       => $validated['first_name'],
            'last_name'        => $validated['last_name'],
            'email'            => $validated['email'],
            'role'             => $validated['role'],
            'password'         => Hash::make($validated['password']),
            'phone'            => $validated['phone'] ?? null,
            'student_staff_id' => $validated['student_staff_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    // Show edit form
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return Inertia::render('Admin/Users/Edit', compact('user'));
    }

    // Update user — business logic untouched
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name'       => ['required', 'string', 'max:255'],
            'last_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'             => ['required', 'in:student,staff,it-support,admin'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'student_staff_id' => ['nullable', 'string', 'max:50'],
            'password'         => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $updateData = [
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'],
            'role'              => $validated['role'],
            'phone'             => $validated['phone'] ?? null,
            'student_staff_id'  => $validated['student_staff_id'] ?? null,
            // Booleans sent via useForm() arrive as true/false — use $request->boolean()
            'is_active'         => $request->boolean('is_active'),
            'email_notifications'=> $request->boolean('email_notifications'),
            'can_submit_tickets' => $request->boolean('can_submit_tickets'),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', 'User updated successfully!');
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $userName = $user->full_name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$userName}' has been deleted successfully!");
    }
}