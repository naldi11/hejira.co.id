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
    if ($user->hasRole('kasir_hendhys'))                  return redirect()->route('hendhys.dashboard');
    return redirect()->route('login');
})->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';
