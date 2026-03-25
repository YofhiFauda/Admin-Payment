<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // \Illuminate\Support\Facades\URL::forceScheme('https');

        // ─── Rate Limit: AI Autofill ────────────────────────────────
        \Illuminate\Support\Facades\RateLimiter::for('ai-auto-fill', function (\Illuminate\Http\Request $request) {
            $key = $request->ip() . ':' . $request->header('X-SECRET', 'no-secret');
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key);
        });

        // ─── Rate Limit: Upload Foto ────────────────────────────────
        \Illuminate\Support\Facades\RateLimiter::for('upload-nota', function (\Illuminate\Http\Request $request) {
            return $request->user()
                ? \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('user:' . $request->user()->id)
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by($request->ip());
        });

        // ─── Rate Limit: Polling ────────────────────────────────────
        \Illuminate\Support\Facades\RateLimiter::for('ocr-polling', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });
    }
}
