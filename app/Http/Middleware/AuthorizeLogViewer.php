<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorizeLogViewer
{
    /**
     * Authorize users to access Log Viewer.
     *
     * Uses Auth::guard('web') explicitly so this works correctly
     * behind a reverse proxy (Nginx/Coolify) where request->user()
     * may not resolve the session context properly.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorize(Request $request): bool
    {
        // Coba guard 'web' terlebih dahulu (eksplisit)
        $user = Auth::guard('web')->user();

        // Fallback: jika masih null, coba dari request langsung
        if (! $user) {
            $user = $request->user();
        }

        return $user && in_array($user->role, ['owner', 'atasan', 'admin']);
    }
}
