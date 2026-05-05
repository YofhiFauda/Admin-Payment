<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HorizonBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Di local environment, skip basic auth
        if (app()->environment('local')) {
            return $next($request);
        }

        // Di production, gunakan basic auth
        $username = env('HORIZON_USERNAME', 'admin');
        $password = env('HORIZON_PASSWORD', 'secret');

        if ($request->getUser() !== $username || $request->getPassword() !== $password) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Horizon"',
            ]);
        }

        return $next($request);
    }
}
