<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class N8nSecretMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.n8n.secret') ?? env('N8N_SECRET');

        // Check header FIRST, then fallback to request body/query
        $providedSecret = $request->header('X-SECRET') ?? $request->input('secret');

        if (!$providedSecret || $providedSecret !== $secret) {
            // ✅ FIX: Log detail untuk debugging mismatch
            Log::channel('ai_autofill')->warning('🔒 [N8N MIDDLEWARE] UNAUTHORIZED REQUEST', [
                'ip'              => $request->ip(),
                'method'          => $request->method(),
                'url'             => $request->fullUrl(),
                'has_header'      => $request->hasHeader('X-SECRET'),
                'has_body_secret' => $request->has('secret'),
                // Tampilkan 4 karakter pertama saja untuk keamanan
                'provided_prefix' => $providedSecret ? substr($providedSecret, 0, 4) . '...' : null,
                'expected_prefix' => $secret ? substr($secret, 0, 4) . '...' : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing secret.'
            ], 401);
        }

        return $next($request);
    }
}
