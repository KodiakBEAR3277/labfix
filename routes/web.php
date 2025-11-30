<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\IT\TicketController;

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
        return view('user.dashboard');
    })->name('dashboard');

    // User routes
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', fn() => view('user.dashboard'))->name('dashboard');
        Route::resource('reports', \App\Http\Controllers\User\ReportController::class)->except(['edit', 'update', 'destroy']);
        
        Route::get('/lab-status', fn() => view('user.lab-status'))->name('lab-status');
        Route::get('/knowledge-base', fn() => view('user.knowledge-base'))->name('knowledge-base');
    });

    // IT routes
    Route::prefix('it')->name('it.')->group(function () {
        Route::get('/dashboard', fn() => view('it.dashboard'))->name('dashboard');
        
        // FIX: Change this route to use the controller method
        Route::get('/assignments', [TicketController::class, 'assignments'])->name('assignments');
        
        Route::get('/knowledge-base', fn() => view('it.knowledge-base'))->name('knowledge-base');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/bulk', [TicketController::class, 'bulk'])->name('tickets.bulk');
        Route::post('/tickets/bulk-update', [TicketController::class, 'bulkUpdate'])->name('tickets.bulk-update');
        Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{id}', [TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{id}/assign-self', [TicketController::class, 'assignToSelf'])->name('tickets.assign-self');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
        Route::get('/labs', fn() => view('admin.labs'))->name('labs');
        Route::get('/settings', fn() => view('admin.settings'))->name('settings');
        
        // User management with full CRUD
        Route::resource('users', AdminUserController::class);
    });
});

// Profile routes (accessible to all authenticated users)
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
});