<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAuth
{
    /**
     * Static authorization method for config callback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizeStatic($request): bool
    {
        // Check if user is authenticated and is owner
        return $request->user() && $request->user()->role === 'owner';
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Check if user is owner
        $user = auth()->user();
        if (!$user || $user->role !== 'owner') {
            return response()->json(['error' => 'Forbidden - Owner access only'], 403);
        }

        return $next($request);
    }
}
