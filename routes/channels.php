<?php
 
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * HELPER: Unified authorization logger
 */
$authorize = function ($channel, $condition, $user, $extra = []) {
    // FAIL-SAFE: User ID 1 (Super Admin) ATAU Role Admin selalu lolos
    $currentRole = strtolower(trim((string)($user->role ?? 'none')));
    
    if (($user->id ?? 0) === 1 || $currentRole === 'admin') {
        $condition = true;
        $extra['bypass_used'] = 'ADMIN_OR_SUPERADMIN_BYPASS';
    }

    $allowed = (bool) $condition;
    
    // Log detail untuk memecahkan misteri 403 (hanya jika denied agar log tidak penuh)
    if (!$allowed) {
        Log::warning("📡 [BROADCAST AUTH] DENIED", [
            'channel'     => $channel,
            'user_id'     => $user->id ?? 'guest',
            'role'        => $currentRole,
            'condition'   => (bool)$condition,
            'extra'       => $extra
        ]);
    }

    return $allowed;
};

Broadcast::channel('App.Models.User.{id}', function ($user, $id) use ($authorize) {
    return $authorize("User.{$id}", (int) $user->id === (int) $id, $user);
});

Broadcast::channel('ocr.{id}', function ($user, $id) use ($authorize) {
    return $authorize("ocr.{$id}", (int) $user->id === (int) $id, $user);
});

Broadcast::channel('notifications.{id}', function ($user, $id) use ($authorize) {
    return $authorize("notifications.{$id}", (int) $user->id === (int) $id, $user);
});

Broadcast::channel('transactions.{id}', function ($user, $id) use ($authorize) {
    return $authorize("transactions.{$id}", (int) $user->id === (int) $id, $user);
});

Broadcast::channel('transactions', function ($user) use ($authorize) {
    return $authorize("transactions", (bool) $user, $user);
});

Broadcast::channel('activities', function ($user) use ($authorize) {
    $allowedRoles = ['admin', 'atasan', 'owner'];
    $currentRole = strtolower(trim((string)($user->role ?? '')));
    $isAllowed = in_array($currentRole, $allowedRoles);
    
    return $authorize("activities", $isAllowed, $user, [
        'allowed' => $allowedRoles
    ]);
});

Broadcast::channel('notifications.management', function ($user) use ($authorize) {
    $allowedRoles = ['owner', 'atasan', 'admin'];
    $currentRole = strtolower(trim((string)($user->role ?? 'none')));
    $isAllowed = in_array($currentRole, $allowedRoles);

    return $authorize("notifications.management", $isAllowed, $user, [
        'allowed' => $allowedRoles,
        'current' => $currentRole
    ]);
});
