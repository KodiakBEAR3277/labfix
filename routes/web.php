<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;

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
        Route::get('/reports', fn() => view('user.reports.index'))->name('reports.index');
        Route::get('/reports/create', fn() => view('user.reports.create'))->name('reports.create');
        Route::get('/reports/{id}', fn($id) => view('user.reports.show', ['id' => $id]))->name('reports.show');
        Route::get('/lab-status', fn() => view('user.lab-status'))->name('lab-status');
        Route::get('/knowledge-base', fn() => view('user.knowledge-base'))->name('knowledge-base');
    });

    // IT routes
    Route::prefix('it')->name('it.')->group(function () {
        Route::get('/dashboard', fn() => view('it.dashboard'))->name('dashboard');
        Route::get('/assignments', fn() => view('it.assignments'))->name('assignments');
        Route::get('/knowledge-base', fn() => view('it.knowledge-base'))->name('knowledge-base');
        Route::get('/tickets', fn() => view('it.tickets.index'))->name('tickets.index');
        Route::get('/tickets/bulk', fn() => view('it.tickets.bulk'))->name('tickets.bulk');
        Route::get('/tickets/{id}', fn($id) => view('it.tickets.show', ['id' => $id]))->name('tickets.show');
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

// Add this inside the Route::middleware('auth')->group(function () { block

// Profile routes (accessible to all authenticated users)
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
});