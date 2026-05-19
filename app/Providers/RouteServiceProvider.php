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
            $limit = config('services.throttle.api_limit', 60);
            return Limit::perMinute($limit)->by($key);
        });

        // ─── Rate Limit: Upload Foto ────────────────────────────────
        // Menggunakan THROTTLE_OCR_LIMIT dari env
        RateLimiter::for('upload-foto', function (Request $request) {
            $limit = config('services.throttle.ocr_limit', 20);
            return $request->user()
                ? Limit::perMinute($limit)->by('user:' . $request->user()->id)
                : Limit::perMinute(max(3, (int)($limit / 5)))->by($request->ip());
        });

        // ─── Rate Limit: Polling ────────────────────────────────────
        // Loading page poll tiap 2 detik = 30/menit, menggunakan THROTTLE_API_LIMIT
        RateLimiter::for('ocr-polling', function (Request $request) {
            $limit = config('services.throttle.api_limit', 60);
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}