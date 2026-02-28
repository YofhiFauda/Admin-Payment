<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ocr.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('transactions.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('transactions', function ($user) {
    // All authenticated users can listen to global transaction table updates.
    // The query on the page respects their roles & permissions anyway.
    return \Illuminate\Support\Facades\Auth::check();
});

Broadcast::channel('activities', function ($user) {
    return in_array($user->role, ['admin', 'atasan', 'owner']);
});
