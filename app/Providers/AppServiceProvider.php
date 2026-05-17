<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
            
            // Force the root URL to match APP_URL to prevent malformed redirects
            // when behind proxies like Cloudflare Tunnel.
            if (config('app.url')) {
                URL::forceRootUrl(config('app.url'));
            }
        }

        // ─── Gate: Log Viewer Authorization ─────────────────────────
        Gate::define('viewLogViewer', function ($user) {
            return $user && $user->role === 'owner';
        });

        // ─── Gate: API Documentation Authorization ──────────────────
        Gate::define('viewApiDocs', function ($user) {
            return $user && $user->role === 'owner';
        });

        // ─── Rate Limit: AI Autofill ────────────────────────────────
        RateLimiter::for('ai-auto-fill', function (Request $request) {
            $key = $request->ip() . ':' . $request->header('X-SECRET', 'no-secret');
            return Limit::perMinute(60)->by($key);
        });

        // ─── Rate Limit: Upload Foto ────────────────────────────────
        RateLimiter::for('upload-nota', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(5)->by('user:' . $request->user()->id)
                : Limit::perMinute(3)->by($request->ip());
        });

        // ─── Rate Limit: Polling ────────────────────────────────────
        RateLimiter::for('ocr-polling', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
