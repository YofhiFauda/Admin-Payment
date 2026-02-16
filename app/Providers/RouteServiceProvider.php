<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('ai-auto-fill', function (Request $request) {

            return [
                // Maks 30 request per menit per IP
                Limit::perMinute(30)->by($request->ip()),

                // Tambahan proteksi berdasarkan secret
                Limit::perMinute(60)->by($request->header('X-SECRET'))
            ];
        });
    }
}
