<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}.notifications', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('gudang.notifications', function ($user) {
    return $user->hasRole('admin_gudang') || $user->hasRole('owner');
});

Broadcast::channel('hendhys.pusat.notifications', function ($user) {
    return ($user->hasRole('kasir_hendhys') && $user->branch->type === 'pusat') || $user->hasRole('owner');
});

Broadcast::channel('owner.notifications', function ($user) {
    return $user->hasRole('owner');
});
