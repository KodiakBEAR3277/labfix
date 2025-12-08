<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\IT\TicketController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\IT\DashboardController as ITDashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\IT\AssignmentController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;

// Public routes
Route::get('/', fn() => view('landing'))->name('landing');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    
    // Dashboard alias
    Route::get('/dashboard', function () {
        $user = Auth::user();
        
        return match($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'it-support' => redirect()->route('it.dashboard'),
            'staff', 'student' => redirect()->route('user.dashboard'),
            default => redirect()->route('user.dashboard'),
        };
    })->name('dashboard');

    // User routes
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::resource('reports', \App\Http\Controllers\User\ReportController::class)->except(['edit', 'update', 'destroy']);
        
        Route::get('/lab-status', fn() => view('user.lab-status'))->name('lab-status');
        Route::get('/knowledge-base', [\App\Http\Controllers\User\KnowledgeBaseController::class, 'index'])->name('knowledge-base');
        Route::get('/knowledge-base/{slug}', [\App\Http\Controllers\User\KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
        Route::post('/knowledge-base/{slug}/helpful', [\App\Http\Controllers\User\KnowledgeBaseController::class, 'markHelpful'])->name('knowledge-base.helpful');
        Route::post('/knowledge-base/{slug}/not-helpful', [\App\Http\Controllers\User\KnowledgeBaseController::class, 'markNotHelpful'])->name('knowledge-base.not-helpful');

    });

    // IT routes - Self-assignment allowed
    Route::prefix('it')->name('it.')->middleware(['auth', 'role:it-support,admin'])->group(function () {
        Route::get('/dashboard', [ITDashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('assignments', AssignmentController::class)->only(['index', 'show', 'edit', 'update']);
        
        // Knowledge Base
        Route::resource('knowledge-base', \App\Http\Controllers\IT\ArticleController::class);
        
        // Tickets - IT can view and self-assign
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{id}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{id}', [TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{id}/assign-self', [TicketController::class, 'assignToSelf'])->name('tickets.assign-self');
        
        // Bulk operations - ADMIN ONLY
        Route::get('/tickets/bulk', [TicketController::class, 'bulk'])->name('tickets.bulk')->middleware('role:admin');
        Route::post('/tickets/bulk-update', [TicketController::class, 'bulkUpdate'])->name('tickets.bulk-update')->middleware('role:admin');
    });

    // Admin routes - Full ticket management
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/settings', fn() => view('admin.settings'))->name('settings');
        
        // User management
        Route::resource('users', AdminUserController::class);
        
        // Lab management
        Route::resource('labs', \App\Http\Controllers\Admin\LabController::class);
        Route::post('/labs/{id}/toggle-status', [\App\Http\Controllers\Admin\LabController::class, 'toggleStatus'])->name('labs.toggle-status');
        
        // Equipment management
        Route::resource('equipment', \App\Http\Controllers\Admin\EquipmentController::class)->except(['index', 'show']);
        
        // Ticket Management (NEW)
        Route::get('/tickets/bulk', [AdminTicketController::class, 'bulk'])->name('tickets.bulk');
        Route::post('/tickets/bulk-update', [AdminTicketController::class, 'bulkUpdate'])->name('tickets.bulk-update');
        Route::resource('tickets', AdminTicketController::class);
    });
});

// Profile routes (accessible to all authenticated users)
Route::prefix('profile')->name('profile.')->middleware('auth')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
});

// API route for loading equipment by lab
Route::get('/api/labs/{lab}/equipment', function ($labId) {
    $equipment = \App\Models\Equipment::where('lab_id', $labId)
        ->orderBy('equipment_code')
        ->get(['id', 'equipment_code', 'status']);
    
    return response()->json($equipment);
});