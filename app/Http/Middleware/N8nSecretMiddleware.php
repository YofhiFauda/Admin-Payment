<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        if (!$request->hasHeader('X-SECRET') || $request->header('X-SECRET') !== $secret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing X-SECRET header.'
            ], 401);
        }

        return $next($request);
    }
}
