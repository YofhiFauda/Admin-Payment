<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        URL::forceScheme('https');

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
