<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING USERS AND ROLES ===\n\n";

echo "Environment: " . config('app.env') . "\n";
echo "APP_URL: " . config('app.url') . "\n\n";

echo "All Users:\n";
$users = \App\Models\User::all(['id', 'name', 'email', 'role']);

if ($users->isEmpty()) {
    echo "  NO USERS FOUND!\n";
} else {
    foreach ($users as $user) {
        $marker = $user->role === 'owner' ? '✓' : ' ';
        echo "  [{$marker}] {$user->name} ({$user->email}) - Role: {$user->role}\n";
    }
}

echo "\n";
$ownerCount = $users->where('role', 'owner')->count();
echo "Total users with 'owner' role: {$ownerCount}\n";

if ($ownerCount === 0) {
    echo "\n⚠️  WARNING: No users with 'owner' role found!\n";
    echo "You need to update a user to 'owner' role to access /docs/api\n\n";
    echo "To fix, run:\n";
    echo "docker exec -it whusnet-app php artisan tinker\n";
    echo "Then in tinker:\n";
    echo "\$user = User::first();\n";
    echo "\$user->role = 'owner';\n";
    echo "\$user->save();\n";
}

echo "\n=== CHECKING MIDDLEWARE ===\n\n";
$middlewareConfig = config('scramble.middleware', []);
echo "Scramble middleware: " . implode(', ', $middlewareConfig) . "\n";

if (in_array(\App\Http\Middleware\AuthorizeApiDocs::class, $middlewareConfig)) {
    echo "✓ Custom middleware is configured\n";
} else {
    echo "✗ Custom middleware NOT found\n";
}

echo "\n=== DONE ===\n";
