<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure Pulse authorization
        Gate::define('viewPulse', function ($user) {
            // Only allow owner and admin roles
            return in_array($user->role, ['owner', 'admin']);
        });
    }
}
