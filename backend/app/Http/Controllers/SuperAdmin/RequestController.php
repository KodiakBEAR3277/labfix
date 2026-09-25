<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\InstitutionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function index()
    {
        $requests = InstitutionRequest::where('status', 'pending')
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/Requests/Index', compact('requests'));
    }

    public function approve($id)
    {
        $institutionRequest = InstitutionRequest::where('status', 'pending')->findOrFail($id);
        $institutionRequest->approve(Auth::user());

        return redirect()
            ->route('superadmin.requests.index')
            ->with('success', "'{$institutionRequest->institution_name}' has been approved.");
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $institutionRequest = InstitutionRequest::where('status', 'pending')->findOrFail($id);
        $institutionRequest->reject(Auth::user(), $validated['reason'] ?? null);

        return redirect()
            ->route('superadmin.requests.index')
            ->with('success', "'{$institutionRequest->institution_name}' has been rejected.");
    }
}