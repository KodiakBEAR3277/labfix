<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Public API routes
Route::post('/login', [AuthController::class, 'login']);

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
    Route::get('/tickets', function() {
        $user = auth()->user();
        
        // Return tickets based on role
        if ($user->role === 'admin') {
            $tickets = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab'])->get();
        } elseif ($user->role === 'it-support') {
            $tickets = \App\Models\Report::where('assigned_to', $user->id)
                ->orWhereNull('assigned_to')
                ->with(['reporter', 'equipment.lab'])
                ->get();
        } else {
            $tickets = \App\Models\Report::where('user_id', $user->id)
                ->with(['assignedTo', 'equipment.lab'])
                ->get();
        }
        
        return response()->json($tickets);
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
});