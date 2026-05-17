<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckApiDocsAccess extends Command
{
    protected $signature = 'api-docs:check {--fix : Automatically set first user as owner}';
    protected $description = 'Check and fix API documentation access';

    public function handle()
    {
        $this->info('=== API Documentation Access Check ===');
        $this->newLine();

        // Check environment
        $this->info('Environment: ' . config('app.env'));
        $this->info('APP_URL: ' . config('app.url'));
        $this->newLine();

        // Check users
        $this->info('All Users:');
        $users = User::all(['id', 'name', 'email', 'role']);

        if ($users->isEmpty()) {
            $this->error('  NO USERS FOUND!');
            return 1;
        }

        $ownerCount = 0;
        foreach ($users as $user) {
            $marker = $user->role === 'owner' ? '✓' : ' ';
            if ($user->role === 'owner') {
                $ownerCount++;
                $this->line("  <fg=green>[{$marker}]</> {$user->name} ({$user->email}) - Role: {$user->role}");
            } else {
                $this->line("  [{$marker}] {$user->name} ({$user->email}) - Role: {$user->role}");
            }
        }

        $this->newLine();
        $this->info("Total users with 'owner' role: {$ownerCount}");

        if ($ownerCount === 0) {
            $this->newLine();
            $this->warn('⚠️  WARNING: No users with owner role found!');
            $this->warn('You need at least one user with owner role to access /docs/api');
            
            if ($this->option('fix')) {
                $this->newLine();
                $firstUser = $users->first();
                $this->info("Setting {$firstUser->name} ({$firstUser->email}) as owner...");
                
                $firstUser->role = 'owner';
                $firstUser->save();
                
                $this->info('✓ User updated to owner role!');
                $this->newLine();
                $this->info('Now you can:');
                $this->info('1. Logout and login again with this user');
                $this->info('2. Visit: ' . config('app.url') . '/docs/api');
            } else {
                $this->newLine();
                $this->info('To fix automatically, run:');
                $this->info('  php artisan api-docs:check --fix');
                $this->newLine();
                $this->info('Or manually update a user:');
                $this->info('  php artisan tinker');
                $this->info('  $user = User::where("email", "your@email.com")->first();');
                $this->info('  $user->role = "owner";');
                $this->info('  $user->save();');
            }
            
            return 1;
        }

        // Check middleware
        $this->newLine();
        $this->info('Middleware Configuration:');
        $middlewareConfig = config('scramble.middleware', []);
        $this->info('  ' . implode(', ', $middlewareConfig));

        if (in_array(\App\Http\Middleware\AuthorizeApiDocs::class, $middlewareConfig)) {
            $this->info('  ✓ Custom middleware is configured');
        } else {
            $this->warn('  ✗ Custom middleware NOT found');
        }

        $this->newLine();
        $this->info('✅ Everything looks good!');
        $this->info('To access API documentation:');
        $this->info('1. Login with an owner account');
        $this->info('2. Visit: ' . config('app.url') . '/docs/api');

        return 0;
    }
}
