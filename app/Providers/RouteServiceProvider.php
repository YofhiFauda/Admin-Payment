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
            $key = $request->ip() . ':' . $request->header('X-SECRET', 'no-secret');
            return Limit::perMinute(60)->by($key);
        });

        // ─── Rate Limit: Upload Foto ────────────────────────────────
        // Max 5 upload per menit per user (tambahan dari throttle di controller)
        RateLimiter::for('upload-foto', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(5)->by('user:' . $request->user()->id)
                : Limit::perMinute(3)->by($request->ip());
        });

        // ─── Rate Limit: Polling ────────────────────────────────────
        // Loading page poll tiap 2 detik = 30/menit, beri limit 60/menit
        RateLimiter::for('ocr-polling', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}