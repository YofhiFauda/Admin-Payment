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
        // Only allow owner and admin roles
        return $request->user() && 
               in_array($request->user()->role, ['owner', 'admin']);
    }
}
