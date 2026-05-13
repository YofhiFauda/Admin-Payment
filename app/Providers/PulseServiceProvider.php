<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     * Pulse vendor provider sudah auto-discovered via package:discover.
     * Provider ini hanya perlu mendefinisikan gate otorisasi dashboard.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function ($user) {
            return in_array($user->role, ['owner', 'atasan', 'admin']);
        });
    }
}
