<?php

/**
 * Debug API Documentation Access
 * 
 * Run: php scripts/debug-api-docs.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════════════\n";
echo " DEBUG API DOCUMENTATION ACCESS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// 1. Check Environment
echo "1️⃣  Environment Configuration:\n";
echo "   APP_ENV: " . config('app.env') . "\n";
echo "   APP_URL: " . config('app.url') . "\n";
echo "   APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "\n";

// 2. Check Scramble Config
echo "2️⃣  Scramble Configuration:\n";
echo "   API Path: " . config('scramble.api_path') . "\n";
echo "   Middleware: " . implode(', ', config('scramble.middleware', [])) . "\n";
echo "\n";

// 3. Check Middleware Exists
echo "3️⃣  Middleware Check:\n";
$middlewareClass = \App\Http\Middleware\AuthorizeApiDocs::class;
if (class_exists($middlewareClass)) {
    echo "   ✓ AuthorizeApiDocs middleware exists\n";
} else {
    echo "   ✗ AuthorizeApiDocs middleware NOT found\n";
}
echo "\n";

// 4. Check Users with Owner Role
echo "4️⃣  Users with 'owner' role:\n";
try {
    $owners = \App\Models\User::where('role', 'owner')->get(['id', 'name', 'email', 'role']);
    if ($owners->isEmpty()) {
        echo "   ⚠ No users with 'owner' role found!\n";
        echo "   You need to create an owner user or update existing user:\n";
        echo "   UPDATE users SET role = 'owner' WHERE email = 'your@email.com';\n";
    } else {
        foreach ($owners as $owner) {
            echo "   ✓ {$owner->name} ({$owner->email})\n";
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Check Routes
echo "5️⃣  Scramble Routes:\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $scrambleRoutes = collect($routes)->filter(function ($route) {
        return str_contains($route->uri(), 'docs/api');
    });
    
    if ($scrambleRoutes->isEmpty()) {
        echo "   ⚠ No routes found for /docs/api\n";
        echo "   Scramble might not be properly installed\n";
    } else {
        foreach ($scrambleRoutes as $route) {
            echo "   ✓ " . $route->methods()[0] . " " . $route->uri() . "\n";
            echo "     Middleware: " . implode(', ', $route->middleware()) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Test Middleware Logic
echo "6️⃣  Middleware Logic Test:\n";
echo "   Environment: " . app()->environment() . "\n";
if (app()->environment('local')) {
    echo "   ✓ In local environment - access will be allowed\n";
} else {
    echo "   ⚠ In production environment - requires authentication and owner role\n";
}
echo "\n";

// 7. Recommendations
echo "═══════════════════════════════════════════════════════════════════\n";
echo " RECOMMENDATIONS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "To access /docs/api in production:\n";
echo "1. Login to the application with an owner account\n";
echo "2. Visit: " . config('app.url') . "/docs/api\n";
echo "\n";

echo "If still getting 403:\n";
echo "1. Clear cache:\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan cache:clear\n";
echo "\n";

echo "2. Check your user role in database:\n";
echo "   SELECT id, name, email, role FROM users WHERE email = 'your@email.com';\n";
echo "\n";

echo "3. Update user role to owner if needed:\n";
echo "   UPDATE users SET role = 'owner' WHERE email = 'your@email.com';\n";
echo "\n";

echo "4. Check Laravel logs:\n";
echo "   tail -f storage/logs/laravel.log\n";
echo "\n";

echo "✅ Debug completed!\n";
