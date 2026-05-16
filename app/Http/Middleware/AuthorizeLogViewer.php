<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorizeLogViewer
{
    /**
     * Authorize users to access Log Viewer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorize(Request $request): bool
    {
        // Menggunakan Auth::guard('web')->user() secara langsung sudah sangat aman.
        // Jika ini null di prod (Coolify), periksa App\Http\Middleware\TrustProxies.
        $user = Auth::guard('web')->user() ?? $request->user();

        return (bool) ($user && $user->isOwner());
    }
}