<?php

namespace App\Providers;

use Laravel\Horizon\Horizon;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider

{   
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon untuk route registration
        try {
            Horizon::routeMailNotificationsTo('admin@whusnet.com');
        } catch (\Exception $e) {
            // Abaikan error jika service belum siap
        }
        
        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');

        // ✅ PENTING: Horizon auth menggunakan callback, bukan Gate
        // Middleware 'auth:web' sudah handle login check, jadi di sini hanya cek role
        Horizon::auth(function ($request) {
            // Allow di local environment
            if (app()->environment('local')) {
                return true;
            }

            // Di production, cek role user (user pasti sudah login karena middleware auth)
            $user = $request->user();
            
            return $user && \in_array($user->role, ['owner', 'admin', 'atasan'], true);
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * NOTE: Gate ini tidak digunakan untuk Horizon auth, tapi bisa digunakan untuk fitur lain
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return app()->environment('local') || in_array($user->role, ['owner', 'admin', 'atasan']);
        });
    }
}