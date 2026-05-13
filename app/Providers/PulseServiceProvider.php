<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\PulseApplicationServiceProvider;

class PulseServiceProvider extends PulseApplicationServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot(); // ✅ WAJIB: panggil parent::boot() dari boot(), bukan dari register()
    }

    /**
     * Authorize access to Pulse dashboard.
     * Override method dari PulseApplicationServiceProvider (sama seperti HorizonServiceProvider).
     */
    protected function gate(): void
    {
        Gate::define('viewPulse', function ($user) {
            return in_array($user->role, ['owner', 'atasan', 'admin']);
        });
    }
}
