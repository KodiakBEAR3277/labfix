<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Models\Setting;
use App\Models\VoiceReport;

// Public API routes
Route::post('/login', [AuthController::class, 'login']);

Route::get('/contact-info', function () {
    return response()->json([
        'system_name'   => Setting::getPlatform('platform_name', 'LabFix'),
        'support_email' => Setting::getPlatform('platform_contact_email', ''),
        'support_phone' => Setting::getPlatform('platform_contact_phone', ''),
    ]);
});

// Protected API routes (require token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Lab endpoints
    Route::get('/labs', function() {
        return response()->json(\App\Models\Lab::with('equipment')->get());
    });
    
    Route::get('/labs/{lab}/equipment', function($labId) {
        $equipment = \App\Models\Equipment::where('lab_id', $labId)->get();
        return response()->json($equipment);
    });
    
    // Ticket endpoints
    Route::get('/tickets', function (Request $request) {
        $user = auth()->user();

        $query = match ($user->role) {
            'admin'      => \App\Models\Report::query(),
            'it-support' => \App\Models\Report::where(function ($q) use ($user) {
                                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
                            }),
            default      => \App\Models\Report::where('user_id', $user->id),
        };

        $query->with(['reporter', 'assignedTo', 'equipment.lab']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active')       $query->open();
            elseif ($request->status === 'resolved') $query->closed();
            else                                      $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(10));
    });
    
    Route::get('/tickets/{id}', function($id) {
        $ticket = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab'])
            ->findOrFail($id);
        
        return response()->json($ticket);
    });
    
    Route::post('/tickets', function(\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'category' => 'required|in:hardware,software,network,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10',
        ]);
        
        $ticket = \App\Models\Report::create([
            'ticket_number' => \App\Models\Report::generateTicketNumber(),
            'user_id' => auth()->id(),
            'equipment_id' => $validated['equipment_id'],
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'new',
            'priority' => 'medium',
        ]);
        
        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => $ticket->load(['equipment.lab'])
        ], 201);
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();

        $recentTickets = \App\Models\Report::where('user_id', $user->id)
            ->with('equipment.lab')
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'active'              => \App\Models\Report::where('user_id', $user->id)->open()->count(),
            'resolved_this_month' => \App\Models\Report::where('user_id', $user->id)
                ->where('status', 'resolved')
                ->whereHas('transactions', function ($query) {
                    $query->where('action', 'status_changed')
                        ->where('new_value', 'resolved')
                        ->whereMonth('created_at', now()->month);
                })
                ->count(),
            'total' => \App\Models\Report::where('user_id', $user->id)->count(),
        ];

        return response()->json([
            'role'           => $user->role,
            'stats'          => $stats,
            'recent_tickets' => $recentTickets,
        ]);
    });

    // Voice dictation audit log — fired after each field-level dictation
    // completes on the mobile report form. Fire-and-forget from the
    // client's perspective: this only records what was said for the
    // audit trail, it never blocks or changes what ends up in the ticket.
    Route::post('/reports/voice-log', function (Request $request) {
        $validated = $request->validate([
            'field'      => ['required', 'in:title,description'],
            'transcript' => ['required', 'string', 'max:2000'],
        ]);

        VoiceReport::create([
            'user_id'                => auth()->id(),
            'transcript'             => $validated['transcript'],
            'extracted_title'        => $validated['field'] === 'title' ? $validated['transcript'] : null,
            'extracted_description'  => $validated['field'] === 'description' ? $validated['transcript'] : null,
        ]);

        return response()->json(['status' => 'logged']);
    });
});