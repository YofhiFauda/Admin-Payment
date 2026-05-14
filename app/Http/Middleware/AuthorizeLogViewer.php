<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

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
        // Explicitly check the web guard to ensure session context is used
        $user = $request->user('web');
        
        return $user && in_array($user->role, ['owner', 'atasan', 'admin']);
    }
}
