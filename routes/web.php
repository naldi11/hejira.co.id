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
    if ($user->hasRole(['kasir_jihans', 'admin_jihans', 'super_admin_jihans'])) return redirect()->route('jihans.dashboard');
    if ($user->hasRole(['kasir_hendhys', 'admin_hendhys', 'super_admin_hendhys'])) return redirect()->route('hendhys.dashboard');
    
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak memiliki role yang valid. Silakan hubungi administrator.']);
})->middleware('auth')->name('dashboard');


Route::middleware('auth')->group(function() {
    Route::get('/api/notifications/counts', [\App\Http\Controllers\Api\NotificationController::class, 'getCounts'])->name('api.notifications.counts');
    Route::get('/select-branch', [\App\Http\Controllers\Auth\BranchSelectionController::class, 'show'])->name('select-branch');
    Route::post('/select-branch', [\App\Http\Controllers\Auth\BranchSelectionController::class, 'select'])->name('select-branch.post');

    // Shared QR Label Printing
    Route::get('/qr/products', [\App\Http\Controllers\Master\ProductQrController::class, 'index'])->name('products.qr');
});

require __DIR__.'/auth.php';

Route::get('/flush-opcache', function() {
    if (!auth()->check() || !auth()->user()->hasRole('owner')) {
        abort(403, 'Unauthorized action.');
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
        return 'OK';
    }
    return 'NO';
});
