<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * System endpoints that sit outside the web/Inertia stack (see routes/system.php):
 * serving uploaded files, and the token-guarded cron entry points.
 */
class SystemController extends Controller
{
    /**
     * Stream an uploaded file from the `public` disk. Keeps the clients on plain
     * /storage/{path} URLs whether the disk is local or S3 (R2).
     */
    public function storage(string $path)
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }

    public function escalate()
    {
        Artisan::call('tickets:auto-escalate');

        return response(Artisan::output(), 200)->header('Content-Type', 'text/plain');
    }

    public function close()
    {
        Artisan::call('tickets:auto-close');

        return response(Artisan::output(), 200)->header('Content-Type', 'text/plain');
    }
}
