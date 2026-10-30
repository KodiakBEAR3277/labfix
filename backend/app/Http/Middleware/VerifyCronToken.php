<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /cron/* endpoints. The external scheduler must send the shared
 * secret in the X-Cron-Token header; anything else gets a 403.
 */
class VerifyCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.cron_secret');
        $provided = (string) $request->header('X-Cron-Token');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            abort(403, 'Invalid cron token.');
        }

        return $next($request);
    }
}
