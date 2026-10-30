<?php

use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System routes — registered OUTSIDE the web/api groups
| (see bootstrap/app.php `withRouting(then: ...)`)
|--------------------------------------------------------------------------
| Deliberately no session, CSRF, Inertia or institution-active middleware, so
| neither the file stream nor the cron pings get tangled up in them.
*/

// Serve uploaded files straight from the `public` disk. In local dev the
// public/storage symlink usually answers first; in production (ephemeral disk /
// S3) this streams the bytes, so web + mobile keep using /storage/{path}.
Route::get('/storage/{path}', [SystemController::class, 'storage'])
    ->where('path', '.*')
    ->name('storage.serve');

// External scheduler entry points (cron-job.org). Each hits one command
// directly, so a slightly-late ping can't be skipped the way `schedule:run`
// would skip a task that isn't due at that exact minute.
Route::middleware(['cron.token', 'throttle:cron'])->group(function () {
    Route::match(['get', 'post'], '/cron/escalate', [SystemController::class, 'escalate'])->name('cron.escalate');
    Route::match(['get', 'post'], '/cron/close', [SystemController::class, 'close'])->name('cron.close');
});
