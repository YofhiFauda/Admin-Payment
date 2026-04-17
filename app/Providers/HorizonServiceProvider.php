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
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return app()->environment('local') || in_array($user->role, ['owner', 'admin', 'atasan']);
        });
    }
}