<?php

use Illuminate\Support\Facades\Route;

// Root: redirect based on auth state
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Generic /dashboard — redirect to role-appropriate dashboard
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasRole('owner'))                          return redirect()->route('owner.dashboard');
    if ($user->hasRole('admin_gudang'))                   return redirect()->route('gudang.dashboard');
    if ($user->hasRole(['kasir_jihans', 'admin_jihans'])) return redirect()->route('jihans.dashboard');
    if ($user->hasRole(['kasir_hendhys', 'admin_hendhys'])) return redirect()->route('hendhys.dashboard');
    return redirect()->route('login');
})->middleware('auth')->name('dashboard');


Route::middleware('auth')->group(function() {
    Route::get('/api/notifications/counts', [\App\Http\Controllers\Api\NotificationController::class, 'getCounts'])->name('api.notifications.counts');
});

require __DIR__.'/auth.php';

Route::get('/flush-opcache', function() { if(function_exists('opcache_reset')){ opcache_reset(); return 'OK'; } return 'NO'; });
