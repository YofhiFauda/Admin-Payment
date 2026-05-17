<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeApiDocs
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access in local environment
        if (app()->environment('local')) {
            return $next($request);
        }

        // In production, check if user is authenticated
        if (!auth()->check()) {
            // Redirect to login with intended URL
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses dokumentasi API.')
                ->with('url.intended', $request->url());
        }

        // Check if user is owner
        $user = auth()->user();
        if (!$user || $user->role !== 'owner') {
            abort(403, 'Hanya owner yang dapat mengakses dokumentasi API. Role Anda: ' . ($user->role ?? 'unknown'));
        }

        return $next($request);
    }
}
